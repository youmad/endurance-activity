<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Recovery;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportRecoveryClaimId;

final readonly class ActivityImportRecoveryClaim
{
    public function __construct(
        public ActivityImportId $importId,
        public ActivityId $activityId,
        public ActivityImportRecoveryClaimId $claimId,
        public int $attemptCount,
        public ?ActivityImportGenerationId $generationId,
    ) {
        if ($attemptCount < 1) {
            throw new \InvalidArgumentException('Activity import recovery attempt count must be positive.');
        }
    }
}
