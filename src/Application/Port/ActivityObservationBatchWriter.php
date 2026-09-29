<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

interface ActivityObservationBatchWriter
{
    /**
     * @param non-empty-list<ActivityObservation> $observations
     */
    public function appendBatch(
        ActivityId $activityId,
        array $observations,
    ): void;
}
