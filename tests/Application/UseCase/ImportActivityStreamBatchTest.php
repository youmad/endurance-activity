<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\BatchingStagedActivityObservationWriter;
use Youmad\Endurance\Activity\Application\Import\ActivityImportGenerationClaim;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemDispatcher;
use Youmad\Endurance\Activity\Application\Import\Handler\ObservationItemHandler;
use Youmad\Endurance\Activity\Application\Import\ObservationItem;
use Youmad\Endurance\Activity\Application\Port\ActivityImportGenerationRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\Port\StagedActivityObservationBatchWriter;
use Youmad\Endurance\Activity\Application\UseCase\ActivityImportGenerationCoordinator;
use Youmad\Endurance\Activity\Application\UseCase\ImportActivityStream;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ImportActivityStreamBatchTest extends TestCase
{
    public function testFullBatchCommitsDuringStreamAndRemainderBeforeActivation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $log = [];
        $batches = [];

        $batchPort = new class($batches, $log) implements StagedActivityObservationBatchWriter {
            /** @var list<non-empty-list<ActivityObservation>> */
            public array $batches;
            /** @var list<string> */
            public array $log;

            /**
             * @param list<non-empty-list<ActivityObservation>> $batches
             * @param list<string>                              $log
             */
            public function __construct(
                array &$batches,
                array &$log,
            ) {
                $this->batches = &$batches;
                $this->log = &$log;
            }

            public function appendBatch(
                ActivityImportGenerationId $generationId,
                ActivityId $activityId,
                array $observations,
            ): void {
                $this->batches[] = $observations;
                $this->log[] = 'batch:'.count($observations);
            }
        };

        $batching = new BatchingStagedActivityObservationWriter(
            writer: $batchPort,
            batchSize: 2,
        );

        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $activities
            ->expects(self::once())
            ->method('save')
            ->with($activity)
            ->willReturnCallback(
                static function () use (&$log): void {
                    $log[] = 'save';
                },
            );

        $repository = $this->repository(
            generationId: $generationId,
            log: $log,
        );

        $this->importer(
            activities: $activities,
            repository: $repository,
            observations: $batching,
        )->handle(
            activityId: $activity->id,
            idempotencyKey: ActivityImportIdempotencyKey::fromString(
                'fit:three-observations',
            ),
            items: [
                $this->item('2026-01-15T10:30:01Z'),
                $this->item('2026-01-15T10:30:02Z'),
                $this->item('2026-01-15T10:30:03Z'),
            ],
        );

        self::assertCount(2, $batches);
        self::assertCount(2, $batches[0]);
        self::assertCount(1, $batches[1]);
        self::assertSame(0, $batching->pendingCount());
        self::assertSame(
            ['claim', 'batch:2', 'batch:1', 'save', 'activate'],
            $log,
        );
    }

    public function testLateFailureLeavesCommittedBatchInvisibleAndFailsGeneration(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $batches = [];
        $log = [];

        $batchPort = new class($batches) implements StagedActivityObservationBatchWriter {
            /** @var list<non-empty-list<ActivityObservation>> */
            public array $batches;

            /** @param list<non-empty-list<ActivityObservation>> $batches */
            public function __construct(array &$batches)
            {
                $this->batches = &$batches;
            }

            public function appendBatch(
                ActivityImportGenerationId $generationId,
                ActivityId $activityId,
                array $observations,
            ): void {
                $this->batches[] = $observations;
            }
        };

        $batching = new BatchingStagedActivityObservationWriter(
            writer: $batchPort,
            batchSize: 2,
        );

        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $activities->expects(self::never())->method('save');

        $items = (function (): iterable {
            yield $this->item('2026-01-15T10:30:01Z');
            yield $this->item('2026-01-15T10:30:02Z');
            throw new \RuntimeException('CRC failed.');
        })();

        try {
            $this->importer(
                activities: $activities,
                repository: $this->repository(
                    generationId: $generationId,
                    log: $log,
                ),
                observations: $batching,
            )->handle(
                activityId: $activity->id,
                idempotencyKey: ActivityImportIdempotencyKey::fromString(
                    'fit:broken',
                ),
                items: $items,
            );

            self::fail('Expected CRC failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('CRC failed.', $exception->getMessage());
        }

        self::assertCount(1, $batches);
        self::assertCount(2, $batches[0]);
        self::assertSame(0, $batching->pendingCount());
        self::assertSame(['claim', 'fail'], $log);
    }

    private function importer(
        ActivityRepository $activities,
        ActivityImportGenerationRepository $repository,
        BatchingStagedActivityObservationWriter $observations,
    ): ImportActivityStream {
        return new ImportActivityStream(
            activities: $activities,
            generations: new ActivityImportGenerationCoordinator(
                generations: $repository,
                transaction: $this->transaction(),
            ),
            dispatcher: new ActivityImportItemDispatcher(
                new ObservationItemHandler($observations),
            ),
        );
    }

    /** @param list<string> $log */
    private function repository(
        ActivityImportGenerationId $generationId,
        array &$log,
    ): ActivityImportGenerationRepository {
        return new class($generationId, $log) implements ActivityImportGenerationRepository {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(
                private readonly ActivityImportGenerationId $generationId,
                array &$log,
            ) {
                $this->log = &$log;
            }

            public function claim(
                ActivityId $activityId,
                ActivityImportIdempotencyKey $idempotencyKey,
            ): ActivityImportGenerationClaim {
                $this->log[] = 'claim';

                return ActivityImportGenerationClaim::acquired(
                    $this->generationId,
                );
            }

            public function activate(
                ActivityImportGenerationId $generationId,
            ): void {
                $this->log[] = 'activate';
            }

            public function fail(
                ActivityImportGenerationId $generationId,
            ): void {
                $this->log[] = 'fail';
            }
        };
    }

    private function transaction(): ActivityTransaction
    {
        return new class implements ActivityTransaction {
            public function run(\Closure $operation): mixed
            {
                return $operation();
            }
        };
    }

    private function item(string $timestamp): ObservationItem
    {
        return new ObservationItem(
            ActivityObservation::create(
                timestamp: $this->instant($timestamp),
                measurements: new ScalarMeasurement(
                    measurementType: MeasurementType::fromString(
                        'heart_rate',
                    ),
                    value: 150,
                    unit: MeasurementUnit::fromSymbol('bpm'),
                ),
            ),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
