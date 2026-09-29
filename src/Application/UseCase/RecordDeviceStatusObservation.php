<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Port\DeviceStatusObservationWriter;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class RecordDeviceStatusObservation implements ActivityImportBatchParticipant
{
    public function __construct(
        private DeviceStatusObservationWriter $observations,
    ) {
    }

    public function handle(
        ActivityId $activityId,
        DeviceStatusObservation $observation,
    ): void {
        try {
            $this->observations->append(
                activityId: $activityId,
                observation: $observation,
            );

            $this->flush();
        } catch (\Throwable $exception) {
            $this->discard();

            throw $exception;
        }
    }

    public function append(
        ActivityId $activityId,
        DeviceStatusObservation $observation,
    ): void {
        $this->observations->append(
            activityId: $activityId,
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
