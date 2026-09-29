<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityDetailWriter
{
    public function append(
        ActivityId $activityId,
        ActivityDetail $detail,
    ): void;
}
