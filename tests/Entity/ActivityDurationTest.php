<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityDurationTest extends TestCase
{
    public function testStartedActivityHasNoElapsedDuration(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        self::assertNull(
            $activity->elapsedDuration(),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testStartedActivityHasNoTimerDuration(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        self::assertNull(
            $activity->timerDuration(),
        );
    }

    public function testElapsedAndTimerDurationsAreEqualWithoutPauses(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        self::assertSame(
            1_800_000_000,
            $activity
                ->elapsedDuration()
                ?->toMicroseconds(),
        );

        self::assertSame(
            1_800_000_000,
            $activity
                ->timerDuration()
                ?->toMicroseconds(),
        );
    }

    public function testTimerDurationExcludesCompletedPause(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:40:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        self::assertSame(
            1_800_000_000,
            $activity
                ->elapsedDuration()
                ?->toMicroseconds(),
        );

        self::assertSame(
            1_500_000_000,
            $activity
                ->timerDuration()
                ?->toMicroseconds(),
        );
    }

    public function testTimerDurationExcludesPauseClosedByFinish(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:40:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        self::assertSame(
            1_800_000_000,
            $activity
                ->elapsedDuration()
                ?->toMicroseconds(),
        );

        self::assertSame(
            600_000_000,
            $activity
                ->timerDuration()
                ?->toMicroseconds(),
        );
    }

    public function testTimerDurationExcludesMultiplePauses(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:40:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:50:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:10:00Z'),
        );

        self::assertSame(
            2_400_000_000,
            $activity
                ->elapsedDuration()
                ?->toMicroseconds(),
        );

        self::assertSame(
            1_500_000_000,
            $activity
                ->timerDuration()
                ?->toMicroseconds(),
        );
    }

    public function testZeroLengthActivityHasZeroDurations(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );

        $activity = Activity::start($startedAt);

        $activity->finish($startedAt);

        self::assertSame(
            0,
            $activity
                ->elapsedDuration()
                ?->toMicroseconds(),
        );

        self::assertSame(
            0,
            $activity
                ->timerDuration()
                ?->toMicroseconds(),
        );
    }
}
