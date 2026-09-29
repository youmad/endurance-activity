<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Device;

use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;

final readonly class ActivityDevice
{
    private function __construct(
        public ActivityDeviceId $id,
        public ?DeviceDescriptor $descriptor,
    ) {
    }

    public static function unknown(
        ActivityDeviceId $id,
    ): self {
        return new self(
            id: $id,
            descriptor: null,
        );
    }

    public static function described(
        ActivityDeviceId $id,
        DeviceDescriptor $descriptor,
    ): self {
        return new self(
            id: $id,
            descriptor: $descriptor,
        );
    }

    public function isDescribed(): bool
    {
        return null !== $this->descriptor;
    }

    public function withDescriptor(
        DeviceDescriptor $descriptor,
    ): self {
        return new self(
            id: $this->id,
            descriptor: $descriptor,
        );
    }
}
