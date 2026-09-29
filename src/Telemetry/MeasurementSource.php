<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;

final readonly class MeasurementSource
{
    private function __construct(
        public ?ActivityDeviceId $deviceId,
        public SourceAttribution $attribution,
    ) {
    }

    public static function explicit(
        ActivityDeviceId $deviceId,
    ): self {
        return new self(
            deviceId: $deviceId,
            attribution: SourceAttribution::Explicit,
        );
    }

    public static function inferred(
        ActivityDeviceId $deviceId,
    ): self {
        return new self(
            deviceId: $deviceId,
            attribution: SourceAttribution::Inferred,
        );
    }

    public static function unknown(): self
    {
        return new self(
            deviceId: null,
            attribution: SourceAttribution::Unknown,
        );
    }

    public function equals(self $other): bool
    {
        if ($this->attribution !== $other->attribution) {
            return false;
        }

        if (
            null === $this->deviceId
            && null === $other->deviceId
        ) {
            return true;
        }

        if (
            null === $this->deviceId
            || null === $other->deviceId
        ) {
            return false;
        }

        return $this->deviceId->equals(
            $other->deviceId,
        );
    }
}
