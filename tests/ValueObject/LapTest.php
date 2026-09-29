<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidLap;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class LapTest extends TestCase
{
    public function testCanCreateLap(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );

        $finishedAt = $this->instant(
            '2026-01-15T11:00:00Z',
        );

        $timerDuration = Duration::fromMicroseconds(
            1_500_000_000,
        );

        $lap = Lap::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: $timerDuration,
        );

        self::assertSame(
            $startedAt,
            $lap->startedAt,
        );

        self::assertSame(
            $finishedAt,
            $lap->finishedAt,
        );

        self::assertSame(
            $timerDuration,
            $lap->timerDuration,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCalculatesElapsedDurationFromItsBoundaries(): void
    {
        $lap = Lap::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_500_000_000,
            ),
        );

        self::assertSame(
            1_800_000_000,
            $lap
                ->elapsedDuration()
                ->toMicroseconds(),
        );
    }

    public function testTimerDurationMayEqualElapsedDuration(): void
    {
        $lap = Lap::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_800_000_000,
            ),
        );

        self::assertTrue(
            $lap->timerDuration->equals(
                $lap->elapsedDuration(),
            ),
        );
    }

    public function testTimerDurationMayBeShorterThanElapsedDuration(): void
    {
        $lap = Lap::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_500_000_000,
            ),
        );

        self::assertFalse(
            $lap->timerDuration->equals(
                $lap->elapsedDuration(),
            ),
        );
    }

    public function testCannotFinishLapBeforeItStarted(): void
    {
        $this->expectException(
            InvalidLap::class,
        );

        Lap::create(
            startedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T10:59:59Z',
            ),
            timerDuration: Duration::zero(),
        );
    }

    public function testPreservesTimerDurationLongerThanElapsedDuration(): void
    {
        $lap = Lap::create(
            startedAt: $this->instant(
                '2026-01-15T10:30:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_800_000_001,
            ),
        );

        self::assertSame(1_800_000_000, $lap->elapsedDuration()->toMicroseconds());
        self::assertSame(1_800_000_001, $lap->timerDuration->toMicroseconds());
    }

    public function testCanCreateZeroLengthLap(): void
    {
        $timestamp = $this->instant(
            '2026-01-15T10:30:00Z',
        );

        $lap = Lap::create(
            startedAt: $timestamp,
            finishedAt: $timestamp,
            timerDuration: Duration::zero(),
        );

        self::assertTrue(
            $lap->elapsedDuration()->equals(
                Duration::zero(),
            ),
        );

        self::assertTrue(
            $lap->timerDuration->equals(
                Duration::zero(),
            ),
        );
    }

    public function testPreservesTimerDurationForZeroLengthLap(): void
    {
        $timestamp = $this->instant(
            '2026-01-15T10:30:00Z',
        );

        $lap = Lap::create(
            startedAt: $timestamp,
            finishedAt: $timestamp,
            timerDuration: Duration::fromMicroseconds(1),
        );

        self::assertSame(0, $lap->elapsedDuration()->toMicroseconds());
        self::assertSame(1, $lap->timerDuration->toMicroseconds());
    }
}
