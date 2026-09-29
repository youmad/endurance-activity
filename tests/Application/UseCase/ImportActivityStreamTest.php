<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportGenerationClaim;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemDispatcher;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\ActivityImportOutcome;
use Youmad\Endurance\Activity\Application\Port\ActivityImportGenerationRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\ActivityImportGenerationCoordinator;
use Youmad\Endurance\Activity\Application\UseCase\ImportActivityStream;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\ActivityImportFailureFinalizationFailed;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ImportActivityStreamTest extends TestCase
{
    public function testFlushesBeforeShortActivationTransaction(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $item = new class implements ActivityImportItem {};
        $log = [];

        $handler = new class($item::class, $log) implements ActivityImportItemHandler, ActivityImportBatchParticipant {
            /** @var list<string> */
            public array $log;
            public ?ActivityImportGenerationId $seenGenerationId = null;

            /** @param list<string> $log */
            public function __construct(
                private readonly string $itemClass,
                array &$log,
            ) {
                $this->log = &$log;
            }

            public function itemClass(): string
            {
                return $this->itemClass;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                $this->seenGenerationId = $context->generationId;
                $this->log[] = 'handle';

                return true;
            }

            public function flush(): void
            {
                $this->log[] = 'flush';
            }

            public function discard(): void
            {
                $this->log[] = 'discard';
            }
        };

        $activities = $this->createMock(ActivityRepository::class);
        $activities
            ->expects(self::once())
            ->method('get')
            ->willReturnCallback(
                static function () use ($activity, &$log): Activity {
                    $log[] = 'get';

                    return $activity;
                },
            );
        $activities
            ->expects(self::once())
            ->method('save')
            ->with($activity)
            ->willReturnCallback(
                static function () use (&$log): void {
                    $log[] = 'save';
                },
            );

        $items = (static function () use ($item, &$log): iterable {
            $log[] = 'yield';
            yield $item;
        })();

        $result = $this->importer(
            activities: $activities,
            generations: $this->generationRepository(
                generationId: $generationId,
                log: $log,
            ),
            dispatcher: new ActivityImportItemDispatcher($handler),
            transaction: $this->transaction($log),
        )->handle(
            activityId: $activity->id,
            idempotencyKey: ActivityImportIdempotencyKey::fromString(
                'fit-file-42',
            ),
            items: $items,
            finalizeImport: static function () use (&$log): void {
                $log[] = 'finalize_import';
            },
        );

        self::assertSame(ActivityImportOutcome::Imported, $result->outcome);
        self::assertTrue($generationId->equals($result->generationId));
        self::assertNotNull($handler->seenGenerationId);
        self::assertTrue(
            $generationId->equals($handler->seenGenerationId),
        );
        self::assertSame(
            [
                'transaction:begin',
                'claim',
                'transaction:end',
                'get',
                'yield',
                'handle',
                'flush',
                'transaction:begin',
                'finalize_import',
                'save',
                'activate',
                'transaction:end',
            ],
            $log,
        );
    }

    public function testActivationFinalizerFailureMarksGenerationFailed(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $activities = $this->createMock(ActivityRepository::class);
        $activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);
        $activities->expects(self::never())->method('save');
        $repository = $this->createMock(
            ActivityImportGenerationRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('claim')
            ->willReturn(
                ActivityImportGenerationClaim::acquired($generationId),
            );
        $repository->expects(self::never())->method('activate');
        $repository
            ->expects(self::once())
            ->method('fail')
            ->with(self::identicalTo($generationId));

        try {
            $this->importer(
                activities: $activities,
                generations: $repository,
                dispatcher: new ActivityImportItemDispatcher(),
                transaction: $this->transaction(),
            )->handle(
                activityId: $activity->id,
                idempotencyKey: ActivityImportIdempotencyKey::fromString(
                    'fit-file-finalizer-failure',
                ),
                items: [],
                finalizeImport: static function (): void {
                    throw new \RuntimeException('Import status could not be completed.');
                },
            );

            self::fail('Expected activation finalizer failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Import status could not be completed.',
                $exception->getMessage(),
            );
        }
    }

    public function testCompletedDuplicateDoesNotReadActivityOrStream(): void
    {
        $generationId = ActivityImportGenerationId::generate();
        $activityId = ActivityId::generate();

        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::never())->method('get');
        $activities->expects(self::never())->method('save');

        $repository = $this->createMock(
            ActivityImportGenerationRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('claim')
            ->willReturn(
                ActivityImportGenerationClaim::alreadyCompleted(
                    $generationId,
                ),
            );
        $repository->expects(self::never())->method('activate');
        $repository->expects(self::never())->method('fail');

        $items = (static function (): iterable {
            yield throw new \LogicException('Completed duplicate must not read the stream.');
        })();

        $result = $this->importer(
            activities: $activities,
            generations: $repository,
            dispatcher: new ActivityImportItemDispatcher(),
            transaction: $this->transaction(),
        )->handle(
            activityId: $activityId,
            idempotencyKey: ActivityImportIdempotencyKey::fromString(
                'fit-file-42',
            ),
            items: $items,
            finalizeImport: static function (): void {
                self::fail('Completed duplicate must not finalize the import.');
            },
        );

        self::assertSame(
            ActivityImportOutcome::AlreadyImported,
            $result->outcome,
        );
        self::assertTrue($generationId->equals($result->generationId));
    }

    public function testLateFailureDiscardsBuffersAndMarksGenerationFailed(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $item = new class implements ActivityImportItem {};
        $log = [];

        $handler = new class($item::class, $log) implements ActivityImportItemHandler, ActivityImportBatchParticipant {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(
                private readonly string $itemClass,
                array &$log,
            ) {
                $this->log = &$log;
            }

            public function itemClass(): string
            {
                return $this->itemClass;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                $this->log[] = 'handle';

                return false;
            }

            public function flush(): void
            {
                $this->log[] = 'flush';
            }

            public function discard(): void
            {
                $this->log[] = 'discard';
            }
        };

        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $activities->expects(self::never())->method('save');

        $items = (static function () use ($item): iterable {
            yield $item;
            throw new \RuntimeException('FIT CRC failed.');
        })();

        try {
            $this->importer(
                activities: $activities,
                generations: $this->generationRepository(
                    generationId: $generationId,
                    log: $log,
                ),
                dispatcher: new ActivityImportItemDispatcher($handler),
                transaction: $this->transaction(),
            )->handle(
                activityId: $activity->id,
                idempotencyKey: ActivityImportIdempotencyKey::fromString(
                    'fit-file-broken',
                ),
                items: $items,
            );

            self::fail('Expected stream failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('FIT CRC failed.', $exception->getMessage());
        }

        self::assertSame(
            ['claim', 'handle', 'discard', 'fail'],
            $log,
        );
    }

    public function testReportsFailureToMarkGenerationFailed(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $repository = $this->createMock(
            ActivityImportGenerationRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('claim')
            ->willReturn(
                ActivityImportGenerationClaim::acquired($generationId),
            );
        $repository
            ->expects(self::once())
            ->method('fail')
            ->with($generationId)
            ->willThrowException(
                new \RuntimeException('Failed to update generation status.'),
            );

        $items = (static function (): iterable {
            yield throw new \RuntimeException('FIT semantic validation failed.');
        })();

        try {
            $this->importer(
                activities: $activities,
                generations: $repository,
                dispatcher: new ActivityImportItemDispatcher(),
                transaction: $this->transaction(),
            )->handle(
                activityId: $activity->id,
                idempotencyKey: ActivityImportIdempotencyKey::fromString(
                    'fit-file-broken-finalization',
                ),
                items: $items,
            );

            self::fail('Expected generation finalization failure.');
        } catch (ActivityImportFailureFinalizationFailed $exception) {
            self::assertSame(
                'FIT semantic validation failed.',
                $exception->importFailure->getMessage(),
            );
            self::assertSame(
                'Failed to update generation status.',
                $exception->getPrevious()?->getMessage(),
            );
        }
    }

    public function testActivatesGenerationWithoutSavingUnchangedAggregate(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $item = new class implements ActivityImportItem {};

        $handler = new class($item::class) implements ActivityImportItemHandler {
            public function __construct(
                private readonly string $itemClass,
            ) {
            }

            public function itemClass(): string
            {
                return $this->itemClass;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                return false;
            }
        };

        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $activities->expects(self::never())->method('save');

        $repository = $this->createMock(
            ActivityImportGenerationRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('claim')
            ->willReturn(
                ActivityImportGenerationClaim::acquired($generationId),
            );
        $repository
            ->expects(self::once())
            ->method('activate')
            ->with($generationId);
        $repository->expects(self::never())->method('fail');

        $this->importer(
            activities: $activities,
            generations: $repository,
            dispatcher: new ActivityImportItemDispatcher($handler),
            transaction: $this->transaction(),
        )->handle(
            activityId: $activity->id,
            idempotencyKey: ActivityImportIdempotencyKey::fromString(
                'device-only-fit',
            ),
            items: [$item],
        );
    }

    private function importer(
        ActivityRepository $activities,
        ActivityImportGenerationRepository $generations,
        ActivityImportItemDispatcher $dispatcher,
        ActivityTransaction $transaction,
    ): ImportActivityStream {
        return new ImportActivityStream(
            activities: $activities,
            generations: new ActivityImportGenerationCoordinator(
                generations: $generations,
                transaction: $transaction,
            ),
            dispatcher: $dispatcher,
        );
    }

    /** @param list<string> $log */
    private function generationRepository(
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

    /** @param list<string>|null $log */
    private function transaction(?array &$log = null): ActivityTransaction
    {
        return new class($log) implements ActivityTransaction {
            /** @var list<string>|null */
            private ?array $log;

            /** @param list<string>|null $log */
            public function __construct(?array &$log)
            {
                $this->log = &$log;
            }

            public function run(\Closure $operation): mixed
            {
                if (null !== $this->log) {
                    $this->log[] = 'transaction:begin';
                }

                try {
                    return $operation();
                } finally {
                    if (null !== $this->log) {
                        $this->log[] = 'transaction:end';
                    }
                }
            }
        };
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
