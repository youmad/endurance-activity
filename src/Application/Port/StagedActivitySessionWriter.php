<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

interface StagedActivitySessionWriter
{
    /**
     * Persists one invisible import row as one atomic short operation.
     */
    public function append(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        ActivitySession $session,
    ): void;
}
