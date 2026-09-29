<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

use Youmad\Endurance\Activity\Application\Recovery\ActivityImportRecoveryClaim;

final class ActivityImportRecoveryClaimLost extends \RuntimeException
{
    public static function forClaim(
        ActivityImportRecoveryClaim $claim,
    ): self {
        return new self(sprintf(
            'Activity import recovery claim %s for import %s is no longer owned.',
            $claim->claimId->toString(),
            $claim->importId->toString(),
        ));
    }
}
