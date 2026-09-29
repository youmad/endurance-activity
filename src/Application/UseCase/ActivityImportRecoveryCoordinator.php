<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityImportRecoveryRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryAction;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryClaim;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryCleanupBatch;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryResult;

final readonly class ActivityImportRecoveryCoordinator
{
    public function __construct(
        private ActivityImportRecoveryRepository $recoveries,
        private ActivityTransaction $transaction,
    ) {
    }

    public function claimNext(
        int $staleAfterSeconds,
        int $recoveryLeaseSeconds,
    ): ?ActivityImportRecoveryClaim {
        if ($staleAfterSeconds < 1 || $recoveryLeaseSeconds < 1) {
            throw new \InvalidArgumentException('Activity import recovery timeouts must be positive.');
        }

        return $this->transaction->run(
            fn (): ?ActivityImportRecoveryClaim => $this->recoveries->claimNext(
                staleAfterSeconds: $staleAfterSeconds,
                recoveryLeaseSeconds: $recoveryLeaseSeconds,
            ),
        );
    }

    public function cleanupBatch(
        ActivityImportRecoveryClaim $claim,
        int $batchSize,
    ): ActivityImportRecoveryCleanupBatch {
        if ($batchSize < 1) {
            throw new \InvalidArgumentException('Activity import recovery cleanup batch size must be positive.');
        }

        return $this->transaction->run(
            fn (): ActivityImportRecoveryCleanupBatch => $this
                ->recoveries
                ->cleanupBatch($claim, $batchSize),
        );
    }

    /** @param \Closure(ActivityImportRecoveryResult): void|null $afterRequeue */
    public function finalize(
        ActivityImportRecoveryClaim $claim,
        int $maxAttempts,
        ?\Closure $afterRequeue = null,
    ): ActivityImportRecoveryResult {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('Activity import recovery max attempts must be positive.');
        }

        return $this->transaction->run(
            function () use (
                $claim,
                $maxAttempts,
                $afterRequeue,
            ): ActivityImportRecoveryResult {
                $result = $this->recoveries->finalize(
                    claim: $claim,
                    maxAttempts: $maxAttempts,
                );

                if (ActivityImportRecoveryAction::Requeued === $result->action) {
                    $afterRequeue?->__invoke($result);
                }

                return $result;
            },
        );
    }
}
