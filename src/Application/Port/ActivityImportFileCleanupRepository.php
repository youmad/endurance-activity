<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Import\ActivityImportFileCleanupClaim;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

interface ActivityImportFileCleanupRepository
{
    /** @return list<ActivityImportFileCleanupClaim> */
    public function claimEligibleFiles(
        Instant $completedBefore,
        Instant $failedBefore,
        Instant $staleClaimBefore,
        int $limit,
    ): array;

    public function markFileDeleted(
        ActivityImportFileCleanupClaim $claim,
    ): void;

    public function releaseFile(
        ActivityImportFileCleanupClaim $claim,
    ): void;

    public function importExists(ActivityImportId $importId): bool;
}
