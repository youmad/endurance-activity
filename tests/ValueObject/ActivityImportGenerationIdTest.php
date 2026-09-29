<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final class ActivityImportGenerationIdTest extends TestCase
{
    public function testRoundTripsStringValue(): void
    {
        $generated = ActivityImportGenerationId::generate();
        $restored = ActivityImportGenerationId::fromString(
            $generated->toString(),
        );

        self::assertTrue($generated->equals($restored));
        self::assertSame(
            $generated->toString(),
            $restored->toString(),
        );
    }
}
