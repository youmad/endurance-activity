<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class StaleActivityImportCandidate
{
    public function __construct(
        public ActivityImportId $importId,
        public ActivityId $activityId,
        public int $attemptCount,
        public Instant $processingStartedAt,
        public Instant $processingHeartbeatAt,
        public Instant $updatedAt,
        public int $idleAgeSeconds,
        public ?ActivityImportGenerationId $generationId,
        public ?string $generationStatus,
        public ?int $generationAttemptCount,
        public ?Instant $generationStagingStartedAt,
        public ?Instant $generationUpdatedAt,
        public ?int $generationIdleAgeSeconds,
        public int $generationRowCount,
    ) {
    }
}
