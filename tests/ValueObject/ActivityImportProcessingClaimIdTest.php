<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\ActivityImportProcessingClaimId;

final class ActivityImportProcessingClaimIdTest extends TestCase
{
    public function testRoundTripsStringValue(): void
    {
        $generated = ActivityImportProcessingClaimId::generate();
        $restored = ActivityImportProcessingClaimId::fromString(
            $generated->toString(),
        );

        self::assertTrue($generated->equals($restored));
        self::assertSame(
            $generated->toString(),
            $restored->toString(),
        );
    }
}
