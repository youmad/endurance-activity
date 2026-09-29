<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityImportRecoveryRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryAction;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryClaim;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryCleanupBatch;
use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryResult;
use Youmad\Endurance\Activity\Application\UseCase\ActivityImportRecoveryCoordinator;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportRecoveryClaimId;

final class ActivityImportRecoveryCoordinatorTest extends TestCase
{
    public function testRunsRecoveryOperationsInTransactionsAndRequeuesInsideFinalizeTransaction(): void
    {
        $importId = ActivityImportId::generate();
        $activityId = ActivityId::fromString($importId->toString());
        $claim = new ActivityImportRecoveryClaim(
            importId: $importId,
            activityId: $activityId,
            claimId: ActivityImportRecoveryClaimId::generate(),
            attemptCount: 1,
            generationId: null,
        );
        $log = [];
        $repository = new class($claim, $log) implements ActivityImportRecoveryRepository {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(
                private readonly ActivityImportRecoveryClaim $claim,
                array &$log,
            ) {
                $this->log = &$log;
            }

            public function claimNext(
                int $staleAfterSeconds,
                int $recoveryLeaseSeconds,
            ): ActivityImportRecoveryClaim {
                $this->log[] = 'claim';

                return $this->claim;
            }

            public function cleanupBatch(
                ActivityImportRecoveryClaim $claim,
                int $batchSize,
            ): ActivityImportRecoveryCleanupBatch {
                $this->log[] = 'cleanup';

                return new ActivityImportRecoveryCleanupBatch(0, true);
            }

            public function finalize(
                ActivityImportRecoveryClaim $claim,
                int $maxAttempts,
            ): ActivityImportRecoveryResult {
                $this->log[] = 'finalize';

                return new ActivityImportRecoveryResult(
                    importId: $claim->importId,
                    activityId: $claim->activityId,
                    attemptCount: $claim->attemptCount,
                    action: ActivityImportRecoveryAction::Requeued,
                    generationId: $claim->generationId,
                );
            }
        };
        $transaction = new class($log) implements ActivityTransaction {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(array &$log)
            {
                $this->log = &$log;
            }

            public function run(\Closure $operation): mixed
            {
                $this->log[] = 'transaction:begin';

                try {
                    return $operation();
                } finally {
                    $this->log[] = 'transaction:end';
                }
            }
        };
        $coordinator = new ActivityImportRecoveryCoordinator(
            recoveries: $repository,
            transaction: $transaction,
        );

        self::assertSame($claim, $coordinator->claimNext(900, 300));
        self::assertTrue($coordinator->cleanupBatch($claim, 100)->complete);
        $coordinator->finalize(
            claim: $claim,
            maxAttempts: 3,
            afterRequeue: static function () use (&$log): void {
                $log[] = 'outbox';
            },
        );

        self::assertSame(
            [
                'transaction:begin',
                'claim',
                'transaction:end',
                'transaction:begin',
                'cleanup',
                'transaction:end',
                'transaction:begin',
                'finalize',
                'outbox',
                'transaction:end',
            ],
            $log,
        );
    }
}
