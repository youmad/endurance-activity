<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\BatchingStagedDeviceStatusObservationWriter;
use Youmad\Endurance\Activity\Application\Port\StagedDeviceStatusObservationBatchWriter;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class BatchingStagedDeviceStatusObservationWriterTest extends TestCase
{
    public function testWritesDeviceStatusesInGenerationBatch(): void
    {
        $generationId = ActivityImportGenerationId::generate();
        $activityId = ActivityId::generate();
        $first = $this->observation('2026-01-15T10:30:01Z');
        $second = $this->observation('2026-01-15T10:30:02Z');
        $batches = [];

        $writer = new class($batches) implements StagedDeviceStatusObservationBatchWriter {
            /** @var list<array{ActivityImportGenerationId, ActivityId, non-empty-list<DeviceStatusObservation>}> */
            public array $batches;

            /** @param list<array{ActivityImportGenerationId, ActivityId, non-empty-list<DeviceStatusObservation>}> $batches */
            public function __construct(array &$batches)
            {
                $this->batches = &$batches;
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

        $batching = new BatchingStagedDeviceStatusObservationWriter(
            writer: $writer,
            batchSize: 2,
        );

        $batching->append($generationId, $activityId, $first);
        $batching->append($generationId, $activityId, $second);

        self::assertCount(1, $batches);
        self::assertSame([$first, $second], $batches[0][2]);
        self::assertTrue($generationId->equals($batches[0][0]));
        self::assertTrue($activityId->equals($batches[0][1]));
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
