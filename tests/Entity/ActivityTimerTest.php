<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Activity\Exception\CannotPauseActivity;
use Youmad\Endurance\Activity\Exception\CannotRecordObservation;
use Youmad\Endurance\Activity\Exception\CannotResumeActivity;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityTimerTest extends TestCase
{
    public function testStartedActivityIsNotPaused(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        self::assertFalse($activity->isPaused());
        self::assertNull($activity->pausedAt);
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testStartedActivityHasZeroAccumulatedPausedDuration(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        self::assertSame(
            0,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testCanPauseActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $pausedAt = $this->instant(
            '2026-01-15T10:45:00Z',
        );

        $activity->pause($pausedAt);

        self::assertTrue($activity->isPaused());

        self::assertTrue(
            $activity->pausedAt?->equals($pausedAt),
        );
    }

    public function testCanPauseActivityAtItsStartTime(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $activity = Activity::start($startedAt);

        $activity->pause($startedAt);

        self::assertTrue($activity->isPaused());
    }

    public function testCannotPauseActivityBeforeItStarted(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $this->expectException(
            CannotPauseActivity::class,
        );

        $activity->pause(
            $this->instant('2026-01-15T10:30:44Z'),
        );
    }

    public function testCannotPauseActivityTwice(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $this->expectException(
            CannotPauseActivity::class,
        );

        $activity->pause(
            $this->instant('2026-01-15T10:50:00Z'),
        );
    }

    public function testCannotPauseFinishedActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $this->expectException(
            CannotPauseActivity::class,
        );

        $activity->pause(
            $this->instant('2026-01-15T11:05:00Z'),
        );
    }

    public function testCannotPauseActivityBeforeLastObservation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:45:00Z',
            ),
        );

        $this->expectException(
            CannotPauseActivity::class,
        );

        $activity->pause(
            $this->instant('2026-01-15T10:44:59Z'),
        );
    }

    private function observation(
        string $timestamp,
    ): ActivityObservation {
        return ActivityObservation::create(
            $this->instant($timestamp),
            $this->positionMeasurement(),
        );
    }

    private function positionMeasurement(): PositionMeasurement
    {
        return new PositionMeasurement(
            new Coordinate(
                latitude: 59.4369,
                longitude: 24.7535,
            ),
        );
    }

    public function testCanRecordObservationWhileActivityIsPaused(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $observation = $this->observation(
            '2026-01-15T10:46:00Z',
        );

        $activity->recordObservation($observation);

        self::assertTrue($activity->isPaused());

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $observation->timestamp,
            ),
        );
    }

    public function testCannotFinishActivityBeforeItWasPaused(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $this->expectException(
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T10:44:59Z'),
        );
    }

    public function testCanFinishPausedActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $finishedAt = $this->instant(
            '2026-01-15T11:00:00Z',
        );

        $activity->finish($finishedAt);

        self::assertTrue(
            $activity->finishedAt?->equals($finishedAt),
        );

        self::assertFalse($activity->isPaused());
        self::assertNull($activity->pausedAt);

        self::assertSame(
            900_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testCanResumeActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:50:00Z'),
        );

        self::assertFalse($activity->isPaused());
        self::assertNull($activity->pausedAt);

        self::assertSame(
            300_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testCanResumeActivityAtPauseTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $pausedAt = $this->instant(
            '2026-01-15T10:45:00Z',
        );

        $activity->pause($pausedAt);
        $activity->resume($pausedAt);

        self::assertFalse($activity->isPaused());

        self::assertSame(
            0,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testCannotResumeActivityThatIsNotPaused(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $this->expectException(
            CannotResumeActivity::class,
        );

        $activity->resume(
            $this->instant('2026-01-15T10:45:00Z'),
        );
    }

    public function testCannotResumeFinishedActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $this->expectException(
            CannotResumeActivity::class,
        );

        $activity->resume(
            $this->instant('2026-01-15T11:05:00Z'),
        );
    }

    public function testCannotResumeActivityBeforeItWasPaused(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $this->expectException(
            CannotResumeActivity::class,
        );

        $activity->resume(
            $this->instant('2026-01-15T10:44:59Z'),
        );
    }

    public function testCannotResumeBeforeLatestPausedObservation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:46:00Z',
            ),
        );

        $this->expectException(
            CannotResumeActivity::class,
        );

        $activity->resume(
            $this->instant('2026-01-15T10:45:30Z'),
        );
    }

    public function testCanRecordObservationAtResumeTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $resumedAt = $this->instant(
            '2026-01-15T10:50:00Z',
        );

        $activity->resume($resumedAt);

        $activity->recordObservation(
            ActivityObservation::create(
                $resumedAt,
                $this->positionMeasurement(),
            ),
        );

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $resumedAt,
            ),
        );
    }

    public function testCannotRecordObservationBeforeResumeTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:50:00Z'),
        );

        $this->expectException(
            CannotRecordObservation::class,
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:49:59Z',
            ),
        );
    }

    public function testCannotFinishActivityBeforeResumeTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:50:00Z'),
        );

        $this->expectException(
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T10:49:59Z'),
        );
    }

    public function testCannotPauseActivityBeforeResumeTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:40:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:50:00Z'),
        );

        $this->expectException(
            CannotPauseActivity::class,
        );

        $activity->pause(
            $this->instant('2026-01-15T10:49:59Z'),
        );
    }

    public function testCanPauseActivityAgainAfterResume(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:40:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $pausedAt = $this->instant(
            '2026-01-15T10:50:00Z',
        );

        $activity->pause($pausedAt);

        self::assertTrue($activity->isPaused());

        self::assertTrue(
            $activity->pausedAt?->equals($pausedAt),
        );

        self::assertSame(
            300_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testCanAccumulateMultiplePausedDurations(): void
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
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $activity->resume(
            $this->instant('2026-01-15T11:10:00Z'),
        );

        self::assertSame(
            900_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testFinishingActiveActivityKeepsAccumulatedPausedDuration(): void
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
            300_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }
}
