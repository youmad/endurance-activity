<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidMeasurement;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;

final class MeasurementMetadataTest extends TestCase
{
    public function testCanCreateCanonicalMeasurementType(): void
    {
        $type = MeasurementType::fromString(
            'heart_rate',
        );

        self::assertSame(
            'heart_rate',
            $type->toString(),
        );
    }

    public function testMeasurementTypesCanBeEqual(): void
    {
        $first = MeasurementType::fromString(
            'heart_rate',
        );

        $second = MeasurementType::fromString(
            'heart_rate',
        );

        self::assertTrue(
            $first->equals($second),
        );
    }

    public function testDifferentMeasurementTypesAreNotEqual(): void
    {
        $first = MeasurementType::fromString(
            'heart_rate',
        );

        $second = MeasurementType::fromString(
            'cadence',
        );

        self::assertFalse(
            $first->equals($second),
        );
    }

    public function testMeasurementTypeMustUseCanonicalNotation(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        MeasurementType::fromString(
            'Heart Rate',
        );
    }

    public function testMeasurementTypeCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        MeasurementType::fromString('');
    }

    public function testCanCreateMeasurementUnit(): void
    {
        $unit = MeasurementUnit::fromSymbol(
            'm/s',
        );

        self::assertSame(
            'm/s',
            $unit->toString(),
        );
    }

    public function testCanCreateUnitlessMeasurementUnit(): void
    {
        $unit = MeasurementUnit::none();

        self::assertSame(
            '1',
            $unit->toString(),
        );
    }

    public function testMeasurementUnitsCanBeEqual(): void
    {
        $first = MeasurementUnit::fromSymbol(
            'bpm',
        );

        $second = MeasurementUnit::fromSymbol(
            'bpm',
        );

        self::assertTrue(
            $first->equals($second),
        );
    }

    public function testMeasurementUnitCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        MeasurementUnit::fromSymbol('');
    }

    public function testMeasurementUnitCannotContainOuterWhitespace(): void
    {
        $this->expectException(
            InvalidMeasurement::class,
        );

        MeasurementUnit::fromSymbol(
            ' bpm ',
        );
    }
}
