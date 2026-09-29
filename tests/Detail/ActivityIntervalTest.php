<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Detail;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Exception\InvalidActivityInterval;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityIntervalTest extends TestCase
{
    public function testCreatesIntervalWithElapsedAndTimerDurations(): void
    {
        $interval = ActivityInterval::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T10:30:25.500000Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                25_250_000,
            ),
        );

        self::assertSame(
            25_500_000,
            $interval->elapsedDuration()->toMicroseconds(),
        );

        self::assertSame(
            25_250_000,
            $interval->timerDuration->toMicroseconds(),
        );
    }

    public function testRejectsTimerDurationLongerThanElapsedDuration(): void
    {
        $this->expectException(
            InvalidActivityInterval::class,
        );

        ActivityInterval::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T10:30:10Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                10_000_001,
            ),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
