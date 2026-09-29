<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

use Youmad\Endurance\Activity\Application\Import\ActivityImportProcessingClaim;

final class ActivityImportProcessingClaimLost extends \RuntimeException
{
    public static function forClaim(
        ActivityImportProcessingClaim $claim,
    ): self {
        return new self(
            sprintf(
                'Activity import %s is no longer owned by processing claim %s.',
                $claim->importId->toString(),
                $claim->claimId->toString(),
            ),
        );
    }
}
