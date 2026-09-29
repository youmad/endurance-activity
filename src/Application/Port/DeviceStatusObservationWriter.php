<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface DeviceStatusObservationWriter
{
    public function append(
        ActivityId $activityId,
        DeviceStatusObservation $observation,
    ): void;
}
