<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityLapReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityLapsReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class GetActivityLaps
{
    public function __construct(
        private ActivityLapReadRepository $laps,
    ) {
    }

    public function find(
        ActivityId $activityId,
    ): ?ActivityLapsReadModel {
        return $this->laps->findForActivity($activityId);
    }
}
