<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\Port\LapWriter;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\Lap;

final readonly class RecordLap
{
    public function __construct(
        private ActivityRepository $activities,
        private LapWriter $laps,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        Lap $lap,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $lap,
            ): void {
                $activity = $this->activities->get(
                    $activityId,
                );

                $this->apply(
                    activity: $activity,
                    lap: $lap,
                );

                $this->activities->save($activity);
            },
        );
    }

    public function apply(
        Activity $activity,
        Lap $lap,
    ): void {
        $activity->recordLap($lap);

        $this->laps->append(
            activityId: $activity->id,
            lap: $lap,
        );
    }
}
