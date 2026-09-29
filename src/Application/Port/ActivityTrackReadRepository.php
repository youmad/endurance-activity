<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Read\ActivityTrackCursor;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackPage;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityTrackReadRepository
{
    public function findPage(
        ActivityId $activityId,
        int $limit,
        ?ActivityTrackCursor $after,
    ): ?ActivityTrackPage;
}
