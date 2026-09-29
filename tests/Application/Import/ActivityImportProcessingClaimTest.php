<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Import;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportProcessingClaim;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportProcessingClaimId;

final class ActivityImportProcessingClaimTest extends TestCase
{
    public function testCarriesAttemptOwnership(): void
    {
        $importId = ActivityImportId::generate();
        $claimId = ActivityImportProcessingClaimId::generate();

        $claim = new ActivityImportProcessingClaim(
            importId: $importId,
            claimId: $claimId,
            attemptCount: 3,
        );

        self::assertTrue($claim->importId->equals($importId));
        self::assertTrue($claim->claimId->equals($claimId));
        self::assertSame(3, $claim->attemptCount);
    }

    public function testRejectsNonPositiveAttemptCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityImportProcessingClaim(
            importId: ActivityImportId::generate(),
            claimId: ActivityImportProcessingClaimId::generate(),
            attemptCount: 0,
        );
    }
}
