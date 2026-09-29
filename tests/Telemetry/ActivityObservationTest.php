<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidActivityObservation;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementOrigin;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\Telemetry\SourceAttribution;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityObservationTest extends TestCase
{
    public function testCanCreateObservationWithUnattributedReportedMeasurement(): void
    {
        $timestamp = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $measurement = $this->positionMeasurement();

        $observation = ActivityObservation::create(
            $timestamp,
            $measurement,
        );

        self::assertSame(
            $timestamp,
            $observation->timestamp,
        );

        self::assertCount(
            1,
            $observation->readings(),
        );

        $reading = $observation->readings()[0];

        self::assertSame(
            $measurement,
            $reading->measurement,
        );

        self::assertSame(
            MeasurementOrigin::Reported,
            $reading->origin,
        );

        self::assertSame(
            SourceAttribution::Unknown,
            $reading->source->attribution,
        );

        self::assertNull(
            $reading->source->deviceId,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    private function positionMeasurement(): PositionMeasurement
    {
        return new PositionMeasurement(
            new Coordinate(
                latitude: 59.4369,
                longitude: 24.7535,
            ),
        );
    }

    public function testCreatePreservesMeasurementOrder(): void
    {
        $position = $this->positionMeasurement();

        $power = $this->powerMeasurement();

        $observation = ActivityObservation::create(
            $this->instant('2026-01-15T10:30:45Z'),
            $position,
            $power,
        );

        self::assertSame(
            $position,
            $observation->readings()[0]->measurement,
        );

        self::assertSame(
            $power,
            $observation->readings()[1]->measurement,
        );
    }

    private function powerMeasurement(): ScalarMeasurement
    {
        return new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'power',
            ),
            value: 250,
            unit: MeasurementUnit::fromSymbol('W'),
        );
    }

    public function testCanCreateObservationWithDifferentMeasurementSources(): void
    {
        $computerId = ActivityDeviceId::generate();
        $trainerId = ActivityDeviceId::generate();

        $positionReading = MeasurementReading::reported(
            measurement: $this->positionMeasurement(),
            source: MeasurementSource::explicit(
                $computerId,
            ),
        );

        $powerReading = MeasurementReading::reported(
            measurement: $this->powerMeasurement(),
            source: MeasurementSource::inferred(
                $trainerId,
            ),
        );

        $observation = ActivityObservation::fromReadings(
            $this->instant('2026-01-15T10:30:45Z'),
            $positionReading,
            $powerReading,
        );

        self::assertSame(
            $positionReading,
            $observation->readings()[0],
        );

        self::assertSame(
            $powerReading,
            $observation->readings()[1],
        );

        self::assertTrue(
            $computerId->equals(
                $observation
                    ->readings()[0]
                    ->source
                    ->deviceId,
            ),
        );

        self::assertTrue(
            $trainerId->equals(
                $observation
                    ->readings()[1]
                    ->source
                    ->deviceId,
            ),
        );

        self::assertSame(
            SourceAttribution::Explicit,
            $observation
                ->readings()[0]
                ->source
                ->attribution,
        );

        self::assertSame(
            SourceAttribution::Inferred,
            $observation
                ->readings()[1]
                ->source
                ->attribution,
        );
    }

    public function testCanCreateObservationWithDerivedMeasurement(): void
    {
        $reading = MeasurementReading::derived(
            measurement: $this->powerMeasurement(),
            source: MeasurementSource::unknown(),
        );

        $observation = ActivityObservation::fromReadings(
            $this->instant('2026-01-15T10:30:45Z'),
            $reading,
        );

        self::assertSame(
            MeasurementOrigin::Derived,
            $observation->readings()[0]->origin,
        );
    }

    public function testCannotCreateObservationWithoutMeasurements(): void
    {
        $this->expectException(
            InvalidActivityObservation::class,
        );

        ActivityObservation::create(
            $this->instant('2026-01-15T10:30:45Z'),
        );
    }

    public function testCannotCreateObservationWithoutReadings(): void
    {
        $this->expectException(
            InvalidActivityObservation::class,
        );

        ActivityObservation::fromReadings(
            $this->instant('2026-01-15T10:30:45Z'),
        );
    }
}
