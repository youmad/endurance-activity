<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Summary;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidActivitySummary;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivitySummaryTest extends TestCase
{
    public function testCreatesSummary(): void
    {
        $reportedAt = $this->instant(
            '2026-01-15T11:00:02Z',
        );

        $timerDuration = Duration::fromMicroseconds(
            5_100_250_000,
        );

        $summary = ActivitySummary::create(
            reportedAt: $reportedAt,
            timerDuration: $timerDuration,
            sessionCount: 2,
            type: 'auto_multi_sport',
            localTimeOffsetSeconds: 20_700,
        );

        self::assertSame(
            $reportedAt,
            $summary->reportedAt,
        );

        self::assertSame(
            $timerDuration,
            $summary->timerDuration,
        );

        self::assertSame(
            2,
            $summary->sessionCount,
        );

        self::assertSame(
            'auto_multi_sport',
            $summary->type,
        );
        self::assertSame(
            20_700,
            $summary->localTimeOffsetSeconds,
        );
    }

    public function testRejectsZeroSessions(): void
    {
        $this->expectException(
            InvalidActivitySummary::class,
        );

        ActivitySummary::create(
            reportedAt: $this->instant(
                '2026-01-15T11:00:02Z',
            ),
            timerDuration: Duration::zero(),
            sessionCount: 0,
        );
    }

    public function testRejectsNonCanonicalType(): void
    {
        $this->expectException(
            InvalidActivitySummary::class,
        );

        ActivitySummary::create(
            reportedAt: $this->instant(
                '2026-01-15T11:00:02Z',
            ),
            timerDuration: Duration::zero(),
            sessionCount: 1,
            type: 'Auto Multi Sport',
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
