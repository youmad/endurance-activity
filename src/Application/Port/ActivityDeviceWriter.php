<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\ActivityDevice;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityDeviceWriter
{
    public function save(
        ActivityId $activityId,
        ActivityDevice $device,
    ): void;
}
