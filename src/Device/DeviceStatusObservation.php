<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Device;

use Youmad\Endurance\Activity\Exception\InvalidDeviceStatusObservation;
use Youmad\Endurance\Activity\Telemetry\Measurement;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class DeviceStatusObservation
{
    /**
     * @var non-empty-list<Measurement>
     */
    private array $measurements;

    /**
     * @param non-empty-list<Measurement> $measurements
     */
    private function __construct(
        public ActivityDeviceId $deviceId,
        public ?Instant $observedAt,
        array $measurements,
    ) {
        $this->measurements = $measurements;
    }

    public static function at(
        ActivityDeviceId $deviceId,
        Instant $observedAt,
        Measurement ...$measurements,
    ): self {
        return self::fromValues(
            deviceId: $deviceId,
            observedAt: $observedAt,
            measurements: $measurements,
        );
    }

    /**
     * @param list<Measurement> $measurements
     */
    private static function fromValues(
        ActivityDeviceId $deviceId,
        ?Instant $observedAt,
        array $measurements,
    ): self {
        if ([] === $measurements) {
            throw new InvalidDeviceStatusObservation('Device status observation must contain at least one measurement.');
        }

        return new self(
            deviceId: $deviceId,
            observedAt: $observedAt,
            measurements: $measurements,
        );
    }

    public static function undated(
        ActivityDeviceId $deviceId,
        Measurement ...$measurements,
    ): self {
        return self::fromValues(
            deviceId: $deviceId,
            observedAt: null,
            measurements: $measurements,
        );
    }

    /**
     * @return non-empty-list<Measurement>
     */
    public function measurements(): array
    {
        return $this->measurements;
    }
}
