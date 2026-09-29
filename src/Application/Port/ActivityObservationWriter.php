<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityObservationWriter
{
    public function append(
        ActivityId $activityId,
        ActivityObservation $observation,
    ): void;
}
