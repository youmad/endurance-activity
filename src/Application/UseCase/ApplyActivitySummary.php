<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class ApplyActivitySummary
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivitySummary $summary,
        ?TemporalResolution $timelineResolution = null,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $summary,
                $timelineResolution,
            ): void {
                $activity = $this->activities->get(
                    $activityId,
                );

                $this->apply(
                    activity: $activity,
                    summary: $summary,
                    timelineResolution: $timelineResolution,
                );

                $this->activities->save($activity);
            },
        );
    }

    public function apply(
        Activity $activity,
        ActivitySummary $summary,
        ?TemporalResolution $timelineResolution = null,
    ): void {
        $activity->applySummary(
            summary: $summary,
            timelineResolution: $timelineResolution,
        );
    }
}
