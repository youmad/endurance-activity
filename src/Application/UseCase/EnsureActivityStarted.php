<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class EnsureActivityStarted
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        Instant $startedAt,
    ): void {
        $this->transaction->run(
            function () use ($activityId, $startedAt): void {
                $activity = Activity::startWithId(
                    id: $activityId,
                    startedAt: $startedAt,
                );

                if ($this->activities->createIfAbsent($activity)) {
                    return;
                }

                $this
                    ->activities
                    ->get($activityId)
                    ->confirmStartedAt($startedAt);
            },
        );
    }
}
