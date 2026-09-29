<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Telemetry\MeasurementOrigin;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\Telemetry\SourceAttribution;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;

final class MeasurementProvenanceTest extends TestCase
{
    public function testCanCreateExplicitMeasurementSource(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $source = MeasurementSource::explicit(
            $deviceId,
        );

        self::assertSame(
            $deviceId,
            $source->deviceId,
        );

        self::assertSame(
            SourceAttribution::Explicit,
            $source->attribution,
        );
    }

    public function testCanCreateInferredMeasurementSource(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $source = MeasurementSource::inferred(
            $deviceId,
        );

        self::assertSame(
            $deviceId,
            $source->deviceId,
        );

        self::assertSame(
            SourceAttribution::Inferred,
            $source->attribution,
        );
    }

    public function testUnknownSourceHasNoDevice(): void
    {
        $source = MeasurementSource::unknown();

        self::assertNull(
            $source->deviceId,
        );

        self::assertSame(
            SourceAttribution::Unknown,
            $source->attribution,
        );
    }

    public function testEqualExplicitSourcesCanBeCompared(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $first = MeasurementSource::explicit(
            $deviceId,
        );

        $second = MeasurementSource::explicit(
            ActivityDeviceId::fromString(
                $deviceId->toString(),
            ),
        );

        self::assertTrue(
            $first->equals($second),
        );
    }

    public function testDifferentAttributionsAreNotEqual(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $explicit = MeasurementSource::explicit(
            $deviceId,
        );

        $inferred = MeasurementSource::inferred(
            $deviceId,
        );

        self::assertFalse(
            $explicit->equals($inferred),
        );
    }

    public function testUnknownSourcesAreEqual(): void
    {
        self::assertTrue(
            MeasurementSource::unknown()->equals(
                MeasurementSource::unknown(),
            ),
        );
    }

    public function testCanCreateReportedReading(): void
    {
        $measurement = $this->powerMeasurement();

        $source = MeasurementSource::explicit(
            ActivityDeviceId::generate(),
        );

        $reading = MeasurementReading::reported(
            measurement: $measurement,
            source: $source,
        );

        self::assertSame(
            $measurement,
            $reading->measurement,
        );

        self::assertSame(
            MeasurementOrigin::Reported,
            $reading->origin,
        );

        self::assertSame(
            $source,
            $reading->source,
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

    public function testCanCreateDerivedReading(): void
    {
        $measurement = $this->powerMeasurement();

        $source = MeasurementSource::unknown();

        $reading = MeasurementReading::derived(
            measurement: $measurement,
            source: $source,
        );

        self::assertSame(
            MeasurementOrigin::Derived,
            $reading->origin,
        );

        self::assertSame(
            $source,
            $reading->source,
        );
    }
}
