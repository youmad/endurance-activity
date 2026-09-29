<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Import;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarning;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarningCode;

final class ActivityImportWarningTest extends TestCase
{
    public function testCreatesStructuredWarning(): void
    {
        $warning = new ActivityImportWarning(
            code: ActivityImportWarningCode::MissingSessionRecovered,
            message: 'Session summary was recovered.',
            context: ['sessionCount' => 1],
        );

        self::assertSame(
            ActivityImportWarningCode::MissingSessionRecovered,
            $warning->code,
        );
        self::assertSame(1, $warning->context['sessionCount']);
    }

    public function testRejectsNumericContextKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityImportWarning(
            code: ActivityImportWarningCode::MissingSessionRecovered,
            message: 'Session summary was recovered.',
            context: [0 => 1],
        );
    }

    public function testRejectsNonFiniteContextFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityImportWarning(
            code: ActivityImportWarningCode::ActivityTimerMismatch,
            message: 'Timer mismatch.',
            context: ['ratio' => INF],
        );
    }

    public function testRejectsInvalidContextKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityImportWarning(
            code: ActivityImportWarningCode::MissingSessionRecovered,
            message: 'Session summary was recovered.',
            context: ['session_count' => 1],
        );
    }
}
