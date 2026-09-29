<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportProcessingClaimId;

final readonly class ActivityImportProcessingClaim
{
    public function __construct(
        public ActivityImportId $importId,
        public ActivityImportProcessingClaimId $claimId,
        public int $attemptCount,
    ) {
        if ($attemptCount < 1) {
            throw new \InvalidArgumentException('Activity import processing attempt count must be positive.');
        }
    }
}
