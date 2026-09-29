<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

final class ActivityImportFailureFinalizationFailed extends \RuntimeException
{
    private function __construct(
        public readonly \Throwable $importFailure,
        \Throwable $finalizationFailure,
    ) {
        parent::__construct(
            message: sprintf(
                'Activity import failed and its generation could not be marked failed: %s Finalization failed: %s',
                $importFailure->getMessage(),
                $finalizationFailure->getMessage(),
            ),
            previous: $finalizationFailure,
        );
    }

    public static function because(
        \Throwable $importFailure,
        \Throwable $finalizationFailure,
    ): self {
        return new self(
            importFailure: $importFailure,
            finalizationFailure: $finalizationFailure,
        );
    }
}
