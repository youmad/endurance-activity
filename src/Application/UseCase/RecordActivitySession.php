<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivitySessionWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class RecordActivitySession
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivitySessionWriter $sessions,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivitySession $session,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $session,
            ): void {
                $activity = $this->activities->get(
                    $activityId,
                );

                $this->apply(
                    activity: $activity,
                    session: $session,
                );

                $this->activities->save($activity);
            },
        );
    }

    public function apply(
        Activity $activity,
        ActivitySession $session,
    ): void {
        $activity->recordSession($session);

        $this->sessions->append(
            activityId: $activity->id,
            session: $session,
        );
    }
}
