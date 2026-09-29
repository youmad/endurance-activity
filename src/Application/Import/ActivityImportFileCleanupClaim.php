<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Foundation\ValueObject\Uuid;

final readonly class ActivityImportFileCleanupClaim
{
    public function __construct(
        public ActivityImportId $importId,
        public Uuid $claimId,
    ) {
    }
}
