<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Recovery;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;

final readonly class ActivityImportRecoveryResult
{
    public function __construct(
        public ActivityImportId $importId,
        public ActivityId $activityId,
        public int $attemptCount,
        public ActivityImportRecoveryAction $action,
        public ?ActivityImportGenerationId $generationId,
    ) {
    }
}
