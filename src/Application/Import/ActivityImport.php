<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityImport
{
    public function __construct(
        public ActivityImportId $id,
        public ActivityId $activityId,
        public ActivityImportStatus $status,
        public int $attemptCount,
        public Instant $createdAt,
        public ?Instant $processingStartedAt,
        public ?Instant $completedAt,
        public ?Instant $failedAt,
        public ?string $errorCode,
        public ?string $errorMessage,
        /** @var list<ActivityImportWarning> */
        public array $warnings = [],
    ) {
    }
}
