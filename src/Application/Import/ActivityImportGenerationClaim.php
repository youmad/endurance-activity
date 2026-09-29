<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final readonly class ActivityImportGenerationClaim
{
    private function __construct(
        public ActivityImportGenerationId $generationId,
        public ActivityImportGenerationClaimStatus $status,
    ) {
    }

    public static function acquired(
        ActivityImportGenerationId $generationId,
    ): self {
        return new self(
            generationId: $generationId,
            status: ActivityImportGenerationClaimStatus::Acquired,
        );
    }

    public static function alreadyCompleted(
        ActivityImportGenerationId $generationId,
    ): self {
        return new self(
            generationId: $generationId,
            status: ActivityImportGenerationClaimStatus::AlreadyCompleted,
        );
    }

    public function isAcquired(): bool
    {
        return ActivityImportGenerationClaimStatus::Acquired
            === $this->status;
    }
}
