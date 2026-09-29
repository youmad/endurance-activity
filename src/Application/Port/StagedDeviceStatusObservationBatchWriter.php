<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

interface StagedDeviceStatusObservationBatchWriter
{
    /**
     * Persists one invisible batch as one atomic short operation.
     *
     * @param non-empty-list<DeviceStatusObservation> $observations
     */
    public function appendBatch(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        array $observations,
    ): void;
}
