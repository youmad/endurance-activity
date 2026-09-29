<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityLifecycleAction;
use Youmad\Endurance\Activity\Application\Import\ActivityLifecycleItem;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class ApplyActivityLifecycleEvent
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivityLifecycleItem $event,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $event,
            ): void {
                $activity = $this->activities->get(
                    $activityId,
                );

                if (!$this->apply($activity, $event)) {
                    return;
                }

                $this->activities->save($activity);
            },
        );
    }

    /**
     * Applies one lifecycle event to an already loaded aggregate.
     *
     * @return bool whether the aggregate must be persisted
     */
    public function apply(
        Activity $activity,
        ActivityLifecycleItem $event,
    ): bool {
        match ($event->action) {
            ActivityLifecycleAction::Start => $activity->confirmStartedAt(
                $event->occurredAt,
            ),
            ActivityLifecycleAction::Pause => $activity->pause(
                $event->occurredAt,
            ),
            ActivityLifecycleAction::Resume => $activity->resume(
                $event->occurredAt,
            ),
            ActivityLifecycleAction::Finish => $activity->finish(
                $event->occurredAt,
            ),
        };

        return ActivityLifecycleAction::Start
            !== $event->action;
    }
}
