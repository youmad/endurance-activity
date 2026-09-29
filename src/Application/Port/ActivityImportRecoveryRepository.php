<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryClaim;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryCleanupBatch;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryResult;

interface ActivityImportRecoveryRepository
{
    public function claimNext(
        int $staleAfterSeconds,
        int $recoveryLeaseSeconds,
    ): ?ActivityImportRecoveryClaim;

    public function cleanupBatch(
        ActivityImportRecoveryClaim $claim,
        int $batchSize,
    ): ActivityImportRecoveryCleanupBatch;

    public function finalize(
        ActivityImportRecoveryClaim $claim,
        int $maxAttempts,
    ): ActivityImportRecoveryResult;
}
