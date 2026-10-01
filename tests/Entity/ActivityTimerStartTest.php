<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotConfirmTimerStart;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityTimerStartTest extends TestCase
{
    public function testTimerStartAndPauseKeepTheirOwnTimesAcrossRestore(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->confirmTimerStartedAt($this->instant(2));
        $activity->pause($this->instant(20));
        $activity = Activity::restore($activity->snapshot());
        $activity->resume($this->instant(23));
        $activity->finish($this->instant(60));

        self::assertTrue($activity->startedAt->equals($this->instant(0)));
        self::assertTrue($activity->timerStartedAt?->equals($this->instant(2)));
        self::assertSame(60_000_000, $activity->elapsedDuration()?->toMicroseconds());
        self::assertSame(55_000_000, $activity->timerDuration()?->toMicroseconds());
        self::assertSame(3_000_000, $activity->accumulatedPausedDuration->toMicroseconds());
    }

    public function testRecordedSummaryDurationRemainsCanonical(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->confirmTimerStartedAt($this->instant(2));
        $activity->recordSession(ActivitySession::create(
            startedAt: $this->instant(0),
            finishedAt: $this->instant(60),
            timerDuration: Duration::fromMicroseconds(59_000_000),
        ));
        $activity->applySummary(ActivitySummary::create(
            reportedAt: $this->instant(65),
            timerDuration: Duration::fromMicroseconds(59_000_000),
            sessionCount: 1,
        ));
        $restored = Activity::restore($activity->snapshot());

        self::assertSame(59_000_000, $restored->timerDuration()?->toMicroseconds());
        self::assertSame(60_000_000, $restored->elapsedDuration()?->toMicroseconds());
        self::assertTrue($restored->timerStartedAt?->equals($this->instant(2)));
    }

    public function testConfirmationIsIdempotent(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->confirmTimerStartedAt($this->instant(2));
        $activity->confirmTimerStartedAt($this->instant(2));
        self::assertTrue($activity->timerStartedAt?->equals($this->instant(2)));
    }

    public function testCannotConfirmDifferentTimerStart(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->confirmTimerStartedAt($this->instant(2));
        $this->expectException(CannotConfirmTimerStart::class);
        $activity->confirmTimerStartedAt($this->instant(3));
    }

    public function testTimerCannotStartBeforeActivity(): void
    {
        $activity = Activity::start($this->instant(2));
        $this->expectException(CannotConfirmTimerStart::class);
        $activity->confirmTimerStartedAt($this->instant(1));
    }

    public function testInitialTimerCannotBeConfirmedWhilePaused(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->pause($this->instant(1));
        $this->expectException(CannotConfirmTimerStart::class);
        $activity->confirmTimerStartedAt($this->instant(2));
    }

    public function testInitialTimerCannotBeConfirmedAfterFinish(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->finish($this->instant(1));
        $this->expectException(CannotConfirmTimerStart::class);
        $activity->confirmTimerStartedAt($this->instant(2));
    }

    public function testFinishMustContainTimerStart(): void
    {
        $activity = Activity::start($this->instant(0));
        $activity->confirmTimerStartedAt($this->instant(2));
        $this->expectException(CannotFinishActivity::class);
        $activity->finish($this->instant(1));
    }

    private function instant(int $second): Instant
    {
        return Instant::fromDateTimeImmutable(
            (new \DateTimeImmutable('2026-01-15T10:30:00Z'))->modify(sprintf('+%d seconds', $second)),
        );
    }
}
