<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

interface StagedDeviceStatusObservationWriter
{
    /**
     * Persists one invisible import row as one atomic short operation.
     */
    public function append(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        DeviceStatusObservation $observation,
    ): void;
}
