<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Read\ActivityReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityReadRepository
{
    public function find(ActivityId $activityId): ?ActivityReadModel;
}
