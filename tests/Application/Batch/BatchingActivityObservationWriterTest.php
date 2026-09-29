<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\BatchingActivityObservationWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityObservationBatchWriter;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class BatchingActivityObservationWriterTest extends TestCase
{
    public function testWritesFullAndFinalPartialBatches(): void
    {
        $activityId = ActivityId::generate();
        $first = $this->observation('2026-01-15T10:30:01Z');
        $second = $this->observation('2026-01-15T10:30:02Z');
        $third = $this->observation('2026-01-15T10:30:03Z');
        /** @var \ArrayObject<int, array{ActivityId, non-empty-list<ActivityObservation>}> $batches */
        $batches = new \ArrayObject();

        $writer = new class($batches) implements ActivityObservationBatchWriter {
            /** @var \ArrayObject<int, array{ActivityId, non-empty-list<ActivityObservation>}> */
            public \ArrayObject $batches;

            /** @param \ArrayObject<int, array{ActivityId, non-empty-list<ActivityObservation>}> $batches */
            public function __construct(\ArrayObject $batches)
            {
                $this->batches = $batches;
            }

            public function appendBatch(
                ActivityId $activityId,
                array $observations,
            ): void {
                $this->batches[] = [
                    $activityId,
                    $observations,
                ];
            }
        };

        $batching = new BatchingActivityObservationWriter(
            writer: $writer,
            batchSize: 2,
        );

        $batching->append($activityId, $first);
        $batching->append($activityId, $second);
        $batching->append($activityId, $third);

        self::assertCount(1, $batches);
        self::assertSame([$first, $second], $batches[0][1]);
        self::assertSame(1, $batching->pendingCount());

        $batching->flush();

        self::assertCount(2, $batches);
        self::assertSame([$third], $batches[1][1]);
        self::assertSame(0, $batching->pendingCount());
    }

    public function testDiscardsPendingObservations(): void
    {
        $writer = $this->createMock(
            ActivityObservationBatchWriter::class,
        );

        $writer
            ->expects(self::never())
            ->method('appendBatch');

        $batching = new BatchingActivityObservationWriter(
            writer: $writer,
            batchSize: 10,
        );

        $batching->append(
            ActivityId::generate(),
            $this->observation('2026-01-15T10:30:01Z'),
        );

        $batching->discard();
        $batching->flush();

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
