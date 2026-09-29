<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Device;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\Exception\InvalidDeviceStatusObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\Telemetry\TextMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class DeviceStatusObservationTest extends TestCase
{
    public function testCanCreateTimestampedDeviceStatusObservation(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $observedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $batteryLevel = new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_level',
            ),
            value: 82,
            unit: MeasurementUnit::fromSymbol('%'),
        );

        $batteryStatus = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_status',
            ),
            value: 'good',
        );

        $observation = DeviceStatusObservation::at(
            $deviceId,
            $observedAt,
            $batteryLevel,
            $batteryStatus,
        );

        self::assertSame(
            $deviceId,
            $observation->deviceId,
        );

        self::assertSame(
            $observedAt,
            $observation->observedAt,
        );

        self::assertSame(
            [
                $batteryLevel,
                $batteryStatus,
            ],
            $observation->measurements(),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCanCreateUndatedDeviceStatusObservation(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $softwareVersion = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'software_version',
            ),
            value: '19.22',
        );

        $observation = DeviceStatusObservation::undated(
            $deviceId,
            $softwareVersion,
        );

        self::assertSame(
            $deviceId,
            $observation->deviceId,
        );

        self::assertNull(
            $observation->observedAt,
        );

        self::assertSame(
            [$softwareVersion],
            $observation->measurements(),
        );
    }

    public function testCanRecordMultipleKindsOfDeviceState(): void
    {
        $deviceId = ActivityDeviceId::generate();

        $batteryVoltage = new ScalarMeasurement(
            measurementType: MeasurementType::fromString(
                'battery_voltage',
            ),
            value: 3.92,
            unit: MeasurementUnit::fromSymbol('V'),
        );

        $hardwareVersion = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'hardware_version',
            ),
            value: '3',
        );

        $sensorPosition = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'sensor_position',
            ),
            value: 'left_crank',
        );

        $sourceType = new TextMeasurement(
            measurementType: MeasurementType::fromString(
                'source_type',
            ),
            value: 'antplus',
        );

        $observation = DeviceStatusObservation::undated(
            $deviceId,
            $batteryVoltage,
            $hardwareVersion,
            $sensorPosition,
            $sourceType,
        );

        self::assertCount(
            4,
            $observation->measurements(),
        );

        self::assertSame(
            'battery_voltage',
            $observation
                ->measurements()[0]
                ->type()
                ->toString(),
        );

        self::assertSame(
            'hardware_version',
            $observation
                ->measurements()[1]
                ->type()
                ->toString(),
        );

        self::assertSame(
            'sensor_position',
            $observation
                ->measurements()[2]
                ->type()
                ->toString(),
        );

        self::assertSame(
            'source_type',
            $observation
                ->measurements()[3]
                ->type()
                ->toString(),
        );
    }

    public function testTimestampedObservationCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidDeviceStatusObservation::class,
        );

        DeviceStatusObservation::at(
            ActivityDeviceId::generate(),
            $this->instant('2026-01-15T10:30:45Z'),
        );
    }

    public function testUndatedObservationCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidDeviceStatusObservation::class,
        );

        DeviceStatusObservation::undated(
            ActivityDeviceId::generate(),
        );
    }
}
