<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityImportGenerationClaim;
use Youmad\Endurance\Activity\Application\Port\ActivityImportGenerationRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

final readonly class ActivityImportGenerationCoordinator
{
    public function __construct(
        private ActivityImportGenerationRepository $generations,
        private ActivityTransaction $transaction,
    ) {
    }

    public function claim(
        ActivityId $activityId,
        ActivityImportIdempotencyKey $idempotencyKey,
    ): ActivityImportGenerationClaim {
        return $this->transaction->run(
            fn (): ActivityImportGenerationClaim => $this
                ->generations
                ->claim(
                    activityId: $activityId,
                    idempotencyKey: $idempotencyKey,
                ),
        );
    }

    /**
     * @param \Closure(): void      $persistAggregate
     * @param \Closure(): void|null $finalizeImport
     */
    public function activate(
        ActivityImportGenerationId $generationId,
        \Closure $persistAggregate,
        ?\Closure $finalizeImport = null,
    ): void {
        $this->transaction->run(
            function () use (
                $generationId,
                $persistAggregate,
                $finalizeImport,
            ): void {
                $finalizeImport?->__invoke();
                $persistAggregate();
                $this->generations->activate($generationId);
            },
        );
    }

    public function fail(
        ActivityImportGenerationId $generationId,
    ): void {
        $this->transaction->run(
            function () use ($generationId): void {
                $this->generations->fail($generationId);
            },
        );
    }
}
