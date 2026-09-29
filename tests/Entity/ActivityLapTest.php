<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Activity\Exception\CannotRecordLap;
use Youmad\Endurance\Activity\Exception\CannotRecordObservation;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivityLapTest extends TestCase
{
    public function testStartedActivityHasNoLastLapFinishTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        self::assertNull(
            $activity->lastLapFinishedAt,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCanRecordLap(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:45:00Z',
        );

        $activity->recordLap($lap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
        );
    }

    private function lap(
        string $startedAt,
        string $finishedAt,
        ?int $timerMicroseconds = null,
    ): Lap {
        $startedAt = $this->instant($startedAt);
        $finishedAt = $this->instant($finishedAt);

        return Lap::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: null === $timerMicroseconds
                ? Duration::between($startedAt, $finishedAt)
                : Duration::fromMicroseconds($timerMicroseconds),
        );
    }

    public function testCannotRecordLapStartingBeforeActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $this->expectException(
            CannotRecordLap::class,
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:29:59Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );
    }

    public function testCanRecordRetrospectiveLapAfterActivityFinished(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:45:00Z',
        );

        $activity->recordLap($lap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
        );
    }

    public function testCannotRecordLapFinishingAfterActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $this->expectException(
            CannotRecordLap::class,
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:45:00Z',
                finishedAt: '2026-01-15T11:00:01Z',
            ),
        );
    }

    public function testCanRecordRetrospectiveLapBeforeLatestObservation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:45:00Z',
            ),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:44:59Z',
        );

        $activity->recordLap($lap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
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

    public function testCanRecordLapAtLatestObservationTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:45:00Z',
            ),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:45:00Z',
        );

        $activity->recordLap($lap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
        );
    }

    public function testCannotRecordOverlappingLap(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $this->expectException(
            CannotRecordLap::class,
        );
        $this->expectExceptionMessage(
            'Lap interval 2026-01-15T10:44:59.000000+00:00..2026-01-15T11:00:00.000000+00:00 overlaps the previous lap ending at 2026-01-15T10:45:00.000000+00:00 by 1000000 microseconds.',
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:44:59Z',
                finishedAt: '2026-01-15T11:00:00Z',
            ),
        );
    }

    public function testAllowsSubSecondOverlapForSecondResolutionLap(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00.551000Z',
            ),
        );

        $secondLap = Lap::create(
            startedAt: $this->instant(
                '2026-01-15T10:45:00Z',
            ),
            finishedAt: $this->instant(
                '2026-01-15T11:00:00Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                900_000_000,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        $activity->recordLap($secondLap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $secondLap->finishedAt,
            ),
        );
    }

    public function testRejectsOverlapBeyondTwoWholeSeconds(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:03Z',
            ),
        );

        $this->expectException(CannotRecordLap::class);
        $this->expectExceptionMessage(
            'by 3000000 microseconds.',
        );

        $activity->recordLap(
            Lap::create(
                startedAt: $this->instant(
                    '2026-01-15T10:45:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T11:00:00Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    900_000_000,
                ),
                timelineResolution: TemporalResolution::Second,
            ),
        );
    }

    public function testCanRecordContiguousLaps(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $secondLap = $this->lap(
            startedAt: '2026-01-15T10:45:00Z',
            finishedAt: '2026-01-15T11:00:00Z',
        );

        $activity->recordLap($secondLap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $secondLap->finishedAt,
            ),
        );
    }

    public function testCanRecordLapsWithGapBetweenThem(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $secondLap = $this->lap(
            startedAt: '2026-01-15T10:50:00Z',
            finishedAt: '2026-01-15T11:00:00Z',
        );

        $activity->recordLap($secondLap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $secondLap->finishedAt,
            ),
        );
    }

    public function testCanRecordLapEndingAtPauseTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:45:00Z'),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:45:00Z',
        );

        $activity->recordLap($lap);

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
        );
    }

    public function testCannotFinishActivityBeforeLastLap(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $this->expectException(
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T10:44:59Z'),
        );
    }

    public function testCanFinishActivityAtLastLapTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $finishedAt = $this->instant(
            '2026-01-15T10:45:00Z',
        );

        $activity->recordLap(
            Lap::create(
                startedAt: $activity->startedAt,
                finishedAt: $finishedAt,
                timerDuration: Duration::between(
                    $activity->startedAt,
                    $finishedAt,
                ),
            ),
        );

        $activity->finish($finishedAt);

        self::assertTrue(
            $activity->finishedAt?->equals($finishedAt),
        );
    }

    public function testCannotRecordObservationBeforeLastLapFinish(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $this->expectException(
            CannotRecordObservation::class,
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:44:59Z',
            ),
        );
    }

    public function testCanRecordObservationAtLastLapFinishTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $finishedAt = $this->instant(
            '2026-01-15T10:45:00Z',
        );

        $activity->recordLap(
            Lap::create(
                startedAt: $activity->startedAt,
                finishedAt: $finishedAt,
                timerDuration: Duration::between(
                    $activity->startedAt,
                    $finishedAt,
                ),
            ),
        );

        $activity->recordObservation(
            ActivityObservation::create(
                $finishedAt,
                $this->positionMeasurement(),
            ),
        );

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $finishedAt,
            ),
        );
    }
}
