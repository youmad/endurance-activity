<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\BatchingDeviceStatusObservationWriter;
use Youmad\Endurance\Activity\Application\Port\DeviceStatusObservationBatchWriter;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class BatchingDeviceStatusObservationWriterTest extends TestCase
{
    public function testWritesDeviceStatusesInBatches(): void
    {
        $activityId = ActivityId::generate();
        $first = $this->observation('2026-01-15T10:30:01Z');
        $second = $this->observation('2026-01-15T10:30:02Z');
        $batches = [];

        $writer = new class($batches) implements DeviceStatusObservationBatchWriter {
            /** @var list<array{ActivityId, non-empty-list<DeviceStatusObservation>}> */
            public array $batches = [];

            /** @param list<array{ActivityId, non-empty-list<DeviceStatusObservation>}> $batches */
            public function __construct(array &$batches)
            {
                $this->batches = &$batches;
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

        $batching = new BatchingDeviceStatusObservationWriter(
            writer: $writer,
            batchSize: 2,
        );

        $batching->append($activityId, $first);
        $batching->append($activityId, $second);

        self::assertCount(1, $batches);
        self::assertSame([$first, $second], $batches[0][1]);
        self::assertSame(0, $batching->pendingCount());
    }

    private function observation(
        string $timestamp,
    ): DeviceStatusObservation {
        return DeviceStatusObservation::at(
            deviceId: ActivityDeviceId::generate(),
            observedAt: Instant::fromDateTimeImmutable(
                new \DateTimeImmutable($timestamp),
            ),
            measurements: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'battery_level',
                ),
                value: 82,
                unit: MeasurementUnit::fromSymbol('%'),
            ),
        );
    }
}
