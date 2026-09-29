<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Import\ActivityImport;
use Youmad\Endurance\Activity\Application\Import\ActivityImportProcessingClaim;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarning;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;

interface ActivityImportRepository
{
    public function enqueue(
        ActivityImportId $importId,
        ActivityId $activityId,
    ): void;

    /**
     * Acquires one processing attempt without stealing an already running one.
     *
     * Returns null when the import is already processing/recovering/completed, terminally failed, or its source
     * file is already being cleaned up.
     */
    public function start(
        ActivityImportId $importId,
    ): ?ActivityImportProcessingClaim;

    /** Refreshes ownership of a live processing attempt. */
    public function heartbeat(ActivityImportProcessingClaim $claim): void;

    /** Releases a recoverable failed attempt back to queued. */
    public function release(ActivityImportProcessingClaim $claim): void;

    /** @param list<ActivityImportWarning> $warnings */
    public function complete(
        ActivityImportProcessingClaim $claim,
        array $warnings = [],
    ): void;

    public function fail(
        ActivityImportProcessingClaim $claim,
        string $errorCode,
        string $errorMessage,
    ): void;

    /**
     * Marks an unowned queued import failed, for example after retries exhaust.
     *
     * Returns false when another worker has already acquired the import or the
     * import is already terminal.
     */
    public function failQueued(
        ActivityImportId $importId,
        string $errorCode,
        string $errorMessage,
    ): bool;

    public function find(ActivityImportId $importId): ?ActivityImport;
}
