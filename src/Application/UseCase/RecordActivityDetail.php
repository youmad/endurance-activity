<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityDetailWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class RecordActivityDetail
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityDetailWriter $details,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivityDetail $detail,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $detail,
            ): void {
                $activity = $this->activities->get(
                    $activityId,
                );

                $this->apply(
                    activity: $activity,
                    detail: $detail,
                );

                $this->activities->save($activity);
            },
        );
    }

    public function apply(
        Activity $activity,
        ActivityDetail $detail,
    ): void {
        $activity->recordDetail($detail);

        $this->details->append(
            activityId: $activity->id,
            detail: $detail,
        );
    }
}
