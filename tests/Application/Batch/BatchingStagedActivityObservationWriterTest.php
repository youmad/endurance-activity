<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\BatchingStagedActivityObservationWriter;
use Youmad\Endurance\Activity\Application\Port\StagedActivityObservationBatchWriter;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class BatchingStagedActivityObservationWriterTest extends TestCase
{
    public function testWritesFullAndFinalPartialBatchesWithGeneration(): void
    {
        $generationId = ActivityImportGenerationId::generate();
        $activityId = ActivityId::generate();
        $first = $this->observation('2026-01-15T10:30:01Z');
        $second = $this->observation('2026-01-15T10:30:02Z');
        $third = $this->observation('2026-01-15T10:30:03Z');
        /** @var \ArrayObject<int, array{ActivityImportGenerationId, ActivityId, non-empty-list<ActivityObservation>}> $batches */
        $batches = new \ArrayObject();

        $writer = new class($batches) implements StagedActivityObservationBatchWriter {
            /** @var \ArrayObject<int, array{ActivityImportGenerationId, ActivityId, non-empty-list<ActivityObservation>}> */
            public \ArrayObject $batches;

            /** @param \ArrayObject<int, array{ActivityImportGenerationId, ActivityId, non-empty-list<ActivityObservation>}> $batches */
            public function __construct(\ArrayObject $batches)
            {
                $this->batches = $batches;
            }

            public function appendBatch(
                ActivityImportGenerationId $generationId,
                ActivityId $activityId,
                array $observations,
            ): void {
                $this->batches[] = [
                    $generationId,
                    $activityId,
                    $observations,
                ];
            }
        };

        $batching = new BatchingStagedActivityObservationWriter(
            writer: $writer,
            batchSize: 2,
        );

        $batching->append($generationId, $activityId, $first);
        $batching->append($generationId, $activityId, $second);
        $batching->append($generationId, $activityId, $third);

        self::assertCount(1, $batches);
        self::assertSame([$first, $second], $batches[0][2]);
        self::assertSame(1, $batching->pendingCount());

        $batching->flush();

        self::assertCount(2, $batches);
        self::assertSame([$third], $batches[1][2]);
        self::assertTrue($generationId->equals($batches[0][0]));
        self::assertTrue($activityId->equals($batches[0][1]));
        self::assertSame(0, $batching->pendingCount());
    }

    private function observation(string $timestamp): ActivityObservation
    {
        return ActivityObservation::create(
            timestamp: Instant::fromDateTimeImmutable(
                new \DateTimeImmutable($timestamp),
            ),
            measurements: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'heart_rate',
                ),
                value: 150,
                unit: MeasurementUnit::fromSymbol('bpm'),
            ),
        );
    }
}
