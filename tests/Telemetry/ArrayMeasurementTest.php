<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidMeasurement;
use Youmad\Endurance\Activity\Telemetry\ArrayMeasurement;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;

final class ArrayMeasurementTest extends TestCase
{
    public function testPreservesOrderedValuesAndInvalidElements(): void
    {
        $measurement = new ArrayMeasurement(
            measurementType: MeasurementType::fromString(
                'power_zones',
            ),
            values: [100, null, 250.5, 'threshold'],
            unit: MeasurementUnit::fromSymbol('W'),
        );

        self::assertSame(
            [100, null, 250.5, 'threshold'],
            $measurement->values(),
        );

        self::assertSame(
            'power_zones',
            $measurement->type()->toString(),
        );

        self::assertSame(
            'W',
            $measurement->unit->toString(),
        );
    }

    public function testRejectsEmptyArray(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new ArrayMeasurement(
            measurementType: MeasurementType::fromString(
                'power_zones',
            ),
            values: [],
            unit: MeasurementUnit::none(),
        );
    }

    public function testRejectsArrayContainingOnlyInvalidElements(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new ArrayMeasurement(
            measurementType: MeasurementType::fromString(
                'power_zones',
            ),
            values: [null, null],
            unit: MeasurementUnit::none(),
        );
    }

    public function testRejectsNonFiniteNumericElement(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new ArrayMeasurement(
            measurementType: MeasurementType::fromString(
                'power_zones',
            ),
            values: [INF],
            unit: MeasurementUnit::none(),
        );
    }
}
