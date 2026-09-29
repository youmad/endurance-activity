<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidMeasurement;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Activity\Telemetry\RangeMeasurement;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\Telemetry\TextMeasurement;
use Youmad\Endurance\Activity\Telemetry\Vector3Measurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;

final class MeasurementFormsTest extends TestCase
{
    public function testPositionMeasurementHasPositionType(): void
    {
        $measurement = new PositionMeasurement(
            new Coordinate(
                latitude: 59.4369,
                longitude: 24.7535,
            ),
        );

        self::assertSame(
            'position',
            $measurement->type()->toString(),
        );
    }

    public function testPositionMeasurementPreservesCoordinate(): void
    {
        $coordinate = new Coordinate(
            latitude: 59.4369,
            longitude: 24.7535,
        );

        $measurement = new PositionMeasurement(
            $coordinate,
        );

        self::assertSame(
            $coordinate,
            $measurement->coordinate,
        );
    }

    public function testCanCreateScalarMeasurement(): void
    {
        $measurement = new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'heart_rate',
            ),
            value: 154,
            unit: MeasurementUnit::fromSymbol('bpm'),
        );

        self::assertSame(
            'heart_rate',
            $measurement->type()->toString(),
        );

        self::assertSame(
            154,
            $measurement->value,
        );

        self::assertSame(
            'bpm',
            $measurement->unit->toString(),
        );
    }

    public function testScalarMeasurementMayContainFloat(): void
    {
        $measurement = new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'speed',
            ),
            value: 8.75,
            unit: MeasurementUnit::fromSymbol('m/s'),
        );

        self::assertSame(
            8.75,
            $measurement->value,
        );
    }

    public function testScalarMeasurementMustBeFinite(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'speed',
            ),
            value: INF,
            unit: MeasurementUnit::fromSymbol('m/s'),
        );
    }

    public function testCanCreateTextMeasurement(): void
    {
        $measurement = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_status',
            ),
            value: 'good',
        );

        self::assertSame(
            'battery_status',
            $measurement->type()->toString(),
        );

        self::assertSame(
            'good',
            $measurement->value,
        );
    }

    public function testTextMeasurementCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_status',
            ),
            value: '',
        );
    }

    public function testTextMeasurementCannotHaveOuterWhitespace(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_status',
            ),
            value: ' good ',
        );
    }

    public function testCanCreateVectorMeasurement(): void
    {
        $measurement = new Vector3Measurement(
            measurementType: MeasurementType::fromString(
                'acceleration',
            ),
            x: 0.25,
            y: -0.50,
            z: 9.81,
            unit: MeasurementUnit::fromSymbol('m/s2'),
        );

        self::assertSame(
            'acceleration',
            $measurement->type()->toString(),
        );

        self::assertSame(
            0.25,
            $measurement->x,
        );

        self::assertSame(
            -0.50,
            $measurement->y,
        );

        self::assertSame(
            9.81,
            $measurement->z,
        );

        self::assertSame(
            'm/s2',
            $measurement->unit->toString(),
        );
    }

    public function testVectorComponentsMustBeFinite(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new Vector3Measurement(
            measurementType: MeasurementType::fromString(
                'acceleration',
            ),
            x: 0.25,
            y: NAN,
            z: 9.81,
            unit: MeasurementUnit::fromSymbol('m/s2'),
        );
    }

    public function testCanCreateRangeMeasurement(): void
    {
        $measurement = new RangeMeasurement(
            measurementType: MeasurementType::fromString(
                'temperature_range',
            ),
            minimum: -5.5,
            maximum: 12.0,
            unit: MeasurementUnit::fromSymbol('degC'),
        );

        self::assertSame(
            'temperature_range',
            $measurement->type()->toString(),
        );

        self::assertSame(
            -5.5,
            $measurement->minimum,
        );

        self::assertSame(
            12.0,
            $measurement->maximum,
        );

        self::assertSame(
            'degC',
            $measurement->unit->toString(),
        );
    }

    public function testRangeMayHaveEqualBoundaries(): void
    {
        $measurement = new RangeMeasurement(
            measurementType: MeasurementType::fromString(
                'temperature_range',
            ),
            minimum: 5.0,
            maximum: 5.0,
            unit: MeasurementUnit::fromSymbol('degC'),
        );

        self::assertSame(
            $measurement->minimum,
            $measurement->maximum,
        );
    }

    public function testRangeMinimumCannotExceedMaximum(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new RangeMeasurement(
            measurementType: MeasurementType::fromString(
                'temperature_range',
            ),
            minimum: 12.0,
            maximum: -5.5,
            unit: MeasurementUnit::fromSymbol('degC'),
        );
    }

    public function testRangeBoundariesMustBeFinite(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        new RangeMeasurement(
            measurementType: MeasurementType::fromString(
                'temperature_range',
            ),
            minimum: -5.5,
            maximum: INF,
            unit: MeasurementUnit::fromSymbol('degC'),
        );
    }
}
