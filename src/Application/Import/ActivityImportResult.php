<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final readonly class ActivityImportResult
{
    private function __construct(
        public ActivityImportGenerationId $generationId,
        public ActivityImportOutcome $outcome,
    ) {
    }

    public static function imported(
        ActivityImportGenerationId $generationId,
    ): self {
        return new self(
            generationId: $generationId,
            outcome: ActivityImportOutcome::Imported,
        );
    }

    public static function alreadyImported(
        ActivityImportGenerationId $generationId,
    ): self {
        return new self(
            generationId: $generationId,
            outcome: ActivityImportOutcome::AlreadyImported,
        );
    }

    public function isImported(): bool
    {
        return ActivityImportOutcome::Imported === $this->outcome;
    }
}
