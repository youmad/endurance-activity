<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivitySessionWriter
{
    public function append(
        ActivityId $activityId,
        ActivitySession $session,
    ): void;
}
