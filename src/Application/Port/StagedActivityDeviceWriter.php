<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Device\ActivityDevice;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

interface StagedActivityDeviceWriter
{
    /**
     * Persists one invisible import row as one atomic short operation.
     */
    public function save(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        ActivityDevice $device,
    ): void;
}
