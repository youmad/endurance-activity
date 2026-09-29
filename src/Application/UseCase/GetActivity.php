<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class GetActivity
{
    public function __construct(
        private ActivityReadRepository $activities,
    ) {
    }

    public function find(ActivityId $activityId): ?ActivityReadModel
    {
        return $this->activities->find($activityId);
    }
}
