<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class StartActivity
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(Instant $startedAt): ActivityId
    {
        $activity = Activity::start($startedAt);

        $this->transaction->run(
            function () use ($activity): void {
                $this->activities->save($activity);
            },
        );

        return $activity->id;
    }
}
