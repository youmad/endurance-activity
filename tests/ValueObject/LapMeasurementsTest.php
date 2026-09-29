<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidLap;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class LapMeasurementsTest extends TestCase
{
    public function testCanPreserveLapReadings(): void
    {
        $reading = $this->reading('custom_score');
        $lap = $this->lap([$reading]);

        self::assertSame(
            [$reading],
            $lap->readings(),
        );
    }

    public function testRejectsDuplicateMeasurementTypes(): void
    {
        $this->expectException(InvalidLap::class);

        $this->lap([
            $this->reading('custom_score'),
            $this->reading('custom_score'),
        ]);
    }

    /**
     * @param list<MeasurementReading> $readings
     */
    private function lap(array $readings): Lap
    {
        $startedAt = Instant::fromDateTimeImmutable(
            new \DateTimeImmutable('2026-01-15T10:30:00Z'),
        );

        $finishedAt = Instant::fromDateTimeImmutable(
            new \DateTimeImmutable('2026-01-15T10:45:00Z'),
        );

        return Lap::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
            readings: $readings,
        );
    }

    private function reading(string $type): MeasurementReading
    {
        return MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString($type),
                value: 42,
                unit: MeasurementUnit::none(),
            ),
            source: MeasurementSource::unknown(),
        );
    }
}
