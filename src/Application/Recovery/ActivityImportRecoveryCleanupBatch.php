<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Recovery;

final readonly class ActivityImportRecoveryCleanupBatch
{
    public function __construct(
        public int $deletedRows,
        public bool $complete,
    ) {
        if ($deletedRows < 0) {
            throw new \InvalidArgumentException('Activity import recovery deleted row count cannot be negative.');
        }
    }
}
