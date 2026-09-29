<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityDeviceWriter;
use Youmad\Endurance\Activity\Device\ActivityDevice;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class SaveActivityDevice
{
    public function __construct(
        private ActivityDeviceWriter $devices,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivityDevice $device,
    ): void {
        $this->devices->save(
            activityId: $activityId,
            device: $device,
        );
    }
}
