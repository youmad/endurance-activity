<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityRepository
{
    public function get(ActivityId $activityId): Activity;

    public function createIfAbsent(Activity $activity): bool;

    public function save(Activity $activity): void;
}
