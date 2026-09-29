<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\Lap;

interface LapWriter
{
    public function append(
        ActivityId $activityId,
        Lap $lap,
    ): void;
}
