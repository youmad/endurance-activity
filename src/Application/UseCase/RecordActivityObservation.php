<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Port\ActivityObservationWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class RecordActivityObservation implements ActivityImportBatchParticipant
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityObservationWriter $observations,
        private ActivityTransaction $transaction,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        ActivityObservation $observation,
    ): void {
        $this->transaction->run(
            function () use (
                $activityId,
                $observation,
            ): void {
                try {
                    $activity = $this->activities->get(
                        $activityId,
                    );

                    $this->apply(
                        activity: $activity,
                        observation: $observation,
                    );

                    $this->flush();
                    $this->activities->save($activity);
                } catch (\Throwable $exception) {
                    $this->discard();

                    throw $exception;
                }
            },
        );
    }

    public function apply(
        Activity $activity,
        ActivityObservation $observation,
    ): void {
        $activity->recordObservation($observation);

        $this->observations->append(
            activityId: $activity->id,
            observation: $observation,
        );
    }

    public function flush(): void
    {
        if (
            $this->observations
            instanceof ActivityImportBatchParticipant
        ) {
            $this->observations->flush();
        }
    }

    public function discard(): void
    {
        if (
            $this->observations
            instanceof ActivityImportBatchParticipant
        ) {
            $this->observations->discard();
        }
    }
}
