<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Read\ActivityLapsReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityLapReadRepository
{
    public function findForActivity(
        ActivityId $activityId,
    ): ?ActivityLapsReadModel;
}
