<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityImport;
use Youmad\Endurance\Activity\Application\Import\ActivityImportProcessingClaim;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarning;
use Youmad\Endurance\Activity\Application\Port\ActivityImportRepository;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;

final readonly class ActivityImportLifecycle
{
    private const int MAX_ERROR_CODE_BYTES = 64;
    private const int MAX_ERROR_MESSAGE_BYTES = 512;
    private const int MAX_WARNING_COUNT = 64;

    public function __construct(
        private ActivityImportRepository $imports,
    ) {
    }

    public function queue(
        ActivityImportId $importId,
        ActivityId $activityId,
    ): void {
        $this->imports->enqueue($importId, $activityId);
    }

    public function start(
        ActivityImportId $importId,
    ): ?ActivityImportProcessingClaim {
        return $this->imports->start($importId);
    }

    public function heartbeat(ActivityImportProcessingClaim $claim): void
    {
        $this->imports->heartbeat($claim);
    }

    public function release(ActivityImportProcessingClaim $claim): void
    {
        $this->imports->release($claim);
    }

    /** @param array<array-key, mixed> $warnings */
    public function complete(
        ActivityImportProcessingClaim $claim,
        array $warnings = [],
    ): void {
        if (self::MAX_WARNING_COUNT < count($warnings)) {
            throw new \InvalidArgumentException('Activity import may contain at most 64 warnings.');
        }

        $validated = [];

        foreach ($warnings as $warning) {
            if (!$warning instanceof ActivityImportWarning) {
                throw new \InvalidArgumentException('Activity import warnings must contain ActivityImportWarning values.');
            }

            $validated[] = $warning;
        }

        $this->imports->complete($claim, $validated);
    }

    public function fail(
        ActivityImportProcessingClaim $claim,
        string $errorCode,
        string $errorMessage,
    ): void {
        $this->validateFailure($errorCode, $errorMessage);
        $this->imports->fail(
            claim: $claim,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }

    public function failQueued(
        ActivityImportId $importId,
        string $errorCode,
        string $errorMessage,
    ): bool {
        $this->validateFailure($errorCode, $errorMessage);

        return $this->imports->failQueued(
            importId: $importId,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }

    public function find(ActivityImportId $importId): ?ActivityImport
    {
        return $this->imports->find($importId);
    }

    private function validateFailure(
        string $errorCode,
        string $errorMessage,
    ): void {
        if (
            '' === $errorCode
            || strlen($errorCode) > self::MAX_ERROR_CODE_BYTES
            || 1 !== preg_match('/^[a-z][a-z0-9_]*$/', $errorCode)
        ) {
            throw new \InvalidArgumentException('Activity import error code must be a short snake_case identifier.');
        }

        if (
            '' === $errorMessage
            || strlen($errorMessage) > self::MAX_ERROR_MESSAGE_BYTES
        ) {
            throw new \InvalidArgumentException('Activity import error message must contain 1 to 512 bytes.');
        }
    }
}
