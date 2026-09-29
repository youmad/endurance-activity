<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface DeviceStatusObservationBatchWriter
{
    /**
     * @param non-empty-list<DeviceStatusObservation> $observations
     */
    public function appendBatch(
        ActivityId $activityId,
        array $observations,
    ): void;
}
