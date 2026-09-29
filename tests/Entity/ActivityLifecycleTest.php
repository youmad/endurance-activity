<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotConfirmActivityStart;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityLifecycleTest extends TestCase
{
    public function testCanStartActivity(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $activity = Activity::start($startedAt);

        self::assertTrue(
            $activity->startedAt->equals($startedAt),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCanConfirmActivityStart(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $activity = Activity::start($startedAt);

        $activity->confirmStartedAt($startedAt);

        self::assertTrue(
            $activity->startedAt->equals($startedAt),
        );
    }

    public function testCannotConfirmDifferentActivityStart(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $this->expectException(
            CannotConfirmActivityStart::class,
        );

        $activity->confirmStartedAt(
            $this->instant('2026-01-15T10:30:46Z'),
        );
    }

    public function testStartedActivitiesHaveDifferentIdentifiers(): void
    {
        $startedAt = $this->instant('2026-01-15T10:30:45Z');
        $first = Activity::start($startedAt);
        $second = Activity::start($startedAt);

        self::assertFalse($first->id->equals($second->id));
    }

    public function testStartedActivityIsNotFinished(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        self::assertNull($activity->finishedAt);
    }

    public function testCanFinishActivityWithoutTrackPoints(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $finishedAt = $this->instant(
            '2026-01-15T11:45:10Z',
        );

        $activity->finish($finishedAt);

        self::assertTrue(
            $activity->finishedAt?->equals($finishedAt),
        );
    }

    public function testCanFinishActivityAtItsStartTime(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $activity = Activity::start($startedAt);

        $activity->finish($startedAt);

        self::assertTrue(
            $activity->finishedAt?->equals($startedAt),
        );
    }

    public function testCannotFinishActivityTwice(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:45:10Z'),
        );

        $this->expectException(
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T12:00:00Z'),
        );
    }

    public function testCannotFinishActivityBeforeItStarted(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $this->expectException(
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T10:30:44Z'),
        );
    }
}
