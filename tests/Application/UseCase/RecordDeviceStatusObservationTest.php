<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\DeviceStatusObservationWriter;
use Youmad\Endurance\Activity\Application\UseCase\RecordDeviceStatusObservation;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\Telemetry\TextMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class RecordDeviceStatusObservationTest extends TestCase
{
    public function testAppendsDeviceStatusObservation(): void
    {
        $activityId = ActivityId::generate();
        $deviceId = ActivityDeviceId::generate();

        $observation = DeviceStatusObservation::at(
            deviceId: $deviceId,
            observedAt: $this->instant(
                '2026-01-15T10:30:45Z',
            ),
            measurements: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'battery_level',
                ),
                value: 82,
                unit: MeasurementUnit::fromSymbol('%'),
            ),
        );

        $observations = $this->createMock(
            DeviceStatusObservationWriter::class,
        );

        $observations
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($actualActivityId): bool => $activityId
                        ->equals($actualActivityId),
                ),
                $observation,
            );

        $useCase = new RecordDeviceStatusObservation(
            observations: $observations,
        );

        $useCase->handle(
            activityId: $activityId,
            observation: $observation,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCanAppendUndatedDeviceStatusObservation(): void
    {
        $activityId = ActivityId::generate();

        $observation = DeviceStatusObservation::undated(
            deviceId: ActivityDeviceId::generate(),
            measurements: new TextMeasurement(
                measurementType: MeasurementType::fromString(
                    'software_version',
                ),
                value: '19.22',
            ),
        );

        $observations = $this->createMock(
            DeviceStatusObservationWriter::class,
        );

        $observations
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($actualActivityId): bool => $activityId
                        ->equals($actualActivityId),
                ),
                $observation,
            );

        $useCase = new RecordDeviceStatusObservation(
            observations: $observations,
        );

        $useCase->handle(
            activityId: $activityId,
            observation: $observation,
        );
    }
}
