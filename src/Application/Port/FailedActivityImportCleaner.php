<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

interface FailedActivityImportCleaner
{
    /**
     * Removes a provisional activity owned exclusively by one failed import.
     *
     * Returns false when the activity is absent or has any active, staging,
     * successful, or differently keyed import state that must be preserved.
     */
    public function discard(
        ActivityId $activityId,
        ActivityImportIdempotencyKey $idempotencyKey,
    ): bool;
}
