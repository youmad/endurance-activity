<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\Pool\PoolLength;
use Youmad\Endurance\Activity\Detail\Pool\PoolLengthType;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotApplyActivitySummary;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivitySummaryTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, TemporalResolution, bool}>
     */
    public static function lapContainmentCases(): iterable
    {
        yield 'Fr935 corpus: exact drift 1.254 seconds, SDK drift one second' => [
            '2019-01-09T04:12:12.031000Z',
            '2019-01-09T04:12:13.285000Z',
            TemporalResolution::Second,
            true,
        ];
        yield 'one whole second is inclusive' => [
            '2019-01-09T04:12:12Z',
            '2019-01-09T04:12:13Z',
            TemporalResolution::Second,
            true,
        ];
        yield 'subseconds retained beyond inclusive whole-second boundary' => [
            '2019-01-09T04:12:12Z',
            '2019-01-09T04:12:13.999000Z',
            TemporalResolution::Second,
            true,
        ];
        yield 'two whole seconds rejected' => [
            '2019-01-09T04:12:12Z',
            '2019-01-09T04:12:14Z',
            TemporalResolution::Second,
            false,
        ];
        yield 'drift below two exact seconds can still fail SDK comparison' => [
            '2019-01-09T04:12:12.999000Z',
            '2019-01-09T04:12:14Z',
            TemporalResolution::Second,
            false,
        ];
        yield 'microsecond source remains exact' => [
            '2019-01-09T04:12:12.031000Z',
            '2019-01-09T04:12:13.285000Z',
            TemporalResolution::Microsecond,
            false,
        ];
    }

    #[DataProvider('lapContainmentCases')]
    public function testSummaryUsesWholeSecondLapContainmentForSecondResolution(
        string $sessionEnd,
        string $lapEnd,
        TemporalResolution $resolution,
        bool $accepted,
    ): void {
        // Timing fields from Activity_20190109_..._186d7297851e0bd250d8e7a8347cac5f.fit.
        $activity = Activity::start(
            $this->instant('2019-01-09T03:33:03Z'),
        );
        $lap = Lap::create(
            startedAt: $this->instant('2019-01-09T04:08:32Z'),
            finishedAt: $this->instant($lapEnd),
            timerDuration: Duration::between(
                $this->instant('2019-01-09T04:08:32Z'),
                $this->instant($lapEnd),
            ),
            timelineResolution: $resolution,
        );
        $activity->recordLap($lap);
        $activity->recordSession(
            $this->session(
                startedAt: '2019-01-09T03:33:03Z',
                finishedAt: $sessionEnd,
                timerMicroseconds: 2_313_795_000,
                timelineResolution: $resolution,
            ),
        );

        if (!$accepted) {
            $this->expectException(CannotApplyActivitySummary::class);
            $this->expectExceptionMessage(
                'Final activity session cannot finish before the latest lap.',
            );
        }

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant('2019-01-09T04:15:11Z'),
                timerDuration: Duration::fromMicroseconds(2_313_795_000),
                sessionCount: 1,
            ),
            timelineResolution: $resolution,
        );

        self::assertTrue($activity->finishedAt?->equals($lap->finishedAt));
        self::assertTrue($activity->lastSessionFinishedAt?->equals(
            $this->instant($sessionEnd),
        ));
        self::assertSame(2_313_795_000, $activity->timerDuration()?->toMicroseconds());
    }

    public function testSummaryFinishesActivityAtFinalSessionEnd(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_700_000_000,
            ),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T11:00:00.500000Z',
                timerMicroseconds: 1_750_250_000,
            ),
        );

        $summary = ActivitySummary::create(
            reportedAt: $this->instant(
                '2026-01-15T11:00:02Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                3_450_250_000,
            ),
            sessionCount: 2,
            type: 'auto_multi_sport',
            localTimeOffsetSeconds: 20_700,
        );

        $activity->applySummary($summary);

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2026-01-15T11:00:00.500000Z',
                ),
            ),
        );

        self::assertSame(
            2,
            $activity->sessionCount,
        );

        self::assertSame(
            3_450_250_000,
            $activity->timerDuration()?->toMicroseconds(),
        );

        self::assertSame(
            'auto_multi_sport',
            $activity->type,
        );
        self::assertSame(
            20_700,
            $activity->localTimeOffsetSeconds,
        );

        self::assertTrue(
            $activity->summaryReportedAt?->equals(
                $summary->reportedAt,
            ),
        );
    }

    public function testSummaryPreservesSubResolutionSessionBoundaryDurations(): void
    {
        $activity = Activity::start(
            $this->instant('2019-07-25T20:24:05Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2019-07-25T20:24:05Z',
                finishedAt: '2019-07-25T20:48:23.595000Z',
                timerMicroseconds: 1_458_595_000,
                timelineResolution: TemporalResolution::Second,
            ),
        );
        $activity->recordSession(
            $this->session(
                startedAt: '2019-07-25T20:48:23Z',
                finishedAt: '2019-07-25T20:51:33.448000Z',
                timerMicroseconds: 190_448_000,
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2019-07-25T20:51:37Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_649_043_000,
                ),
                sessionCount: 2,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2019-07-25T20:51:34.043000Z',
                ),
            ),
        );
        self::assertSame(
            1_649_043_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
    }

    public function testSummaryPreservesCumulativeSubResolutionSessionDurations(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:10:00.600000Z',
                timerMicroseconds: 600_600_000,
                timelineResolution: TemporalResolution::Second,
            ),
        );
        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:10:00Z',
                finishedAt: '2026-01-15T10:20:00.500000Z',
                timerMicroseconds: 600_500_000,
                timelineResolution: TemporalResolution::Second,
            ),
        );
        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:20:00Z',
                finishedAt: '2026-01-15T10:30:00.400000Z',
                timerMicroseconds: 600_400_000,
                timelineResolution: TemporalResolution::Second,
            ),
        );
        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:30:01.500000Z',
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:05Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_801_500_000,
                ),
                sessionCount: 3,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2026-01-15T10:30:01.500000Z',
                ),
            ),
        );
        self::assertSame(
            1_801_500_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
        self::assertSame(
            0,
            $activity->snapshot()
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );
    }

    public function testSummaryMayConfirmAlreadyFinishedActivityWithinOneSecond(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T10:30:01Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00.500000Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:02Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
                type: 'manual',
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertSame(
            1_800_000_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
    }

    public function testRejectsSessionCountMismatch(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $this->expectException(
            CannotApplyActivitySummary::class,
        );

        $this->expectExceptionMessage(
            'declares 2 sessions, but 1 sessions were recorded',
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 2,
            ),
        );
    }

    public function testUsesRecordedSessionTimerWhenActivitySummaryDiffers(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_700_000_000,
            ),
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_709_000_000,
                ),
                sessionCount: 1,
            ),
        );

        self::assertSame(
            1_700_000_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
    }

    public function testRejectsFinalSessionBeforeLatestActivityDetail(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordDetail(
            PoolLength::create(
                interval: ActivityInterval::create(
                    startedAt: $this->instant(
                        '2026-01-15T10:29:30Z',
                    ),
                    finishedAt: $this->instant(
                        '2026-01-15T10:30:01Z',
                    ),
                    timerDuration: Duration::fromMicroseconds(
                        31_000_000,
                    ),
                ),
                type: PoolLengthType::Active,
                stroke: 'freestyle',
            ),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $this->expectException(
            CannotApplyActivitySummary::class,
        );

        $this->expectExceptionMessage(
            'cannot finish before the latest activity detail',
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:02Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
            ),
        );
    }

    public function testSecondResolutionContainsRoundedObservationBoundary(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordObservation(
            $this->observation('2026-01-15T10:30:01Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00.545000Z',
                timerMicroseconds: 1_800_545_000,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:15Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_545_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant('2026-01-15T10:30:01Z'),
            ),
        );
        self::assertSame(
            1_800_545_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
    }

    public function testSecondResolutionContainsSubSecondLapBoundaryDrift(): void
    {
        $activity = Activity::start(
            $this->instant('2019-07-25T20:24:05Z'),
        );

        $activity->recordLap(
            Lap::create(
                startedAt: $this->instant(
                    '2019-07-25T20:48:23Z',
                ),
                finishedAt: $this->instant(
                    '2019-07-25T20:51:33.448000Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    190_448_000,
                ),
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2019-07-25T20:24:05Z',
                finishedAt: '2019-07-25T20:51:32.595000Z',
                timerMicroseconds: 1_647_595_000,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2019-07-25T20:51:37Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_647_595_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2019-07-25T20:51:33.448000Z',
                ),
            ),
        );
    }

    public function testSecondResolutionContainsSubSecondLifecycleBoundaryDrift(): void
    {
        $activity = Activity::start(
            $this->instant('1990-07-09T00:00:00Z'),
        );

        $activity->pause(
            $this->instant('1990-07-09T00:57:07Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '1990-07-09T00:00:00Z',
                finishedAt: '1990-07-09T00:57:06.898000Z',
                timerMicroseconds: 3_426_898_000,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '1990-07-09T00:57:11Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    3_426_898_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant('1990-07-09T00:57:07Z'),
            ),
        );
        self::assertSame(
            3_426_898_000,
            $activity->timerDuration()?->toMicroseconds(),
        );
    }

    public function testSecondResolutionRejectsLifecycleBoundaryAtResolutionLimit(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->pause(
            $this->instant('2026-01-15T10:30:01Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $this->expectException(
            CannotApplyActivitySummary::class,
        );
        $this->expectExceptionMessage(
            'cannot finish before the latest event',
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:15Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );
    }

    public function testSecondResolutionRejectsObservationBeyondRoundedBoundary(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordObservation(
            $this->observation('2026-01-15T10:30:02Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00.545000Z',
                timerMicroseconds: 1_800_545_000,
            ),
        );

        $this->expectException(
            CannotApplyActivitySummary::class,
        );
        $this->expectExceptionMessage(
            'cannot finish before the latest observation',
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:15Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_545_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );
    }

    public function testSecondResolutionIncludesOneSecondObservationExcess(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordObservation(
            $this->observation('2026-01-15T10:30:01Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:15Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );
        self::assertTrue($activity->finishedAt?->equals(
            $this->instant('2026-01-15T10:30:01Z'),
        ));
        self::assertTrue($activity->lastSessionFinishedAt?->equals(
            $this->instant('2026-01-15T10:30:00Z'),
        ));
        self::assertSame(1_800_000_000, $activity->timerDuration()?->toMicroseconds());
    }

    public function testMicrosecondResolutionRejectsOneSecondObservationExcess(): void
    {
        $activity = Activity::start($this->instant('2026-01-15T10:00:00Z'));
        $activity->recordObservation($this->observation('2026-01-15T10:30:01Z'));
        $activity->recordSession($this->session(
            startedAt: '2026-01-15T10:00:00Z',
            finishedAt: '2026-01-15T10:30:00Z',
            timerMicroseconds: 1_800_000_000,
        ));
        $this->expectException(CannotApplyActivitySummary::class);
        $this->expectExceptionMessage('cannot finish before the latest observation');
        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant('2026-01-15T10:30:15Z'),
                timerDuration: Duration::fromMicroseconds(1_800_000_000),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Microsecond,
        );
    }

    public function testAcceptedObservationDoesNotHideLaterLifecycleBoundary(): void
    {
        $activity = Activity::start($this->instant('2026-01-15T10:00:00Z'));
        $activity->recordObservation($this->observation('2026-01-15T10:30:01Z'));
        $activity->pause($this->instant('2026-01-15T10:30:02Z'));
        $activity->recordSession($this->session(
            startedAt: '2026-01-15T10:00:00Z',
            finishedAt: '2026-01-15T10:30:00Z',
            timerMicroseconds: 1_800_000_000,
        ));
        $this->expectException(CannotApplyActivitySummary::class);
        $this->expectExceptionMessage('cannot finish before the latest event');
        $activity->applySummary(
            summary: ActivitySummary::create(
                reportedAt: $this->instant('2026-01-15T10:30:15Z'),
                timerDuration: Duration::fromMicroseconds(1_800_000_000),
                sessionCount: 1,
            ),
            timelineResolution: TemporalResolution::Second,
        );
    }

    public function testApplyingSameSummaryTwiceIsIdempotent(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $summary = ActivitySummary::create(
            reportedAt: $this->instant(
                '2026-01-15T10:30:01Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_800_000_000,
            ),
            sessionCount: 1,
            type: 'manual',
        );

        $activity->applySummary($summary);
        $activity->applySummary($summary);

        self::assertSame(
            'manual',
            $activity->type,
        );
    }

    public function testSameSummaryMayBackfillMissingLocalTimeOffset(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );
        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
                type: 'manual',
            ),
        );
        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
                type: 'manual',
                localTimeOffsetSeconds: -25_200,
            ),
        );

        self::assertSame(
            -25_200,
            $activity->localTimeOffsetSeconds,
        );
    }

    public function testSameSummaryRejectsConflictingKnownLocalTimeOffset(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );
        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
                timerMicroseconds: 1_800_000_000,
            ),
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
                type: 'manual',
                localTimeOffsetSeconds: -25_200,
            ),
        );

        $this->expectException(CannotApplyActivitySummary::class);
        $this->expectExceptionMessage(
            'A different activity summary has already been applied.',
        );

        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:01Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_000_000,
                ),
                sessionCount: 1,
                type: 'manual',
                localTimeOffsetSeconds: -21_600,
            ),
        );
    }

    private function observation(string $timestamp): ActivityObservation
    {
        return ActivityObservation::create(
            $this->instant($timestamp),
            new PositionMeasurement(
                new Coordinate(
                    latitude: 59.4369,
                    longitude: 24.7535,
                ),
            ),
        );
    }

    private function session(
        string $startedAt,
        string $finishedAt,
        int $timerMicroseconds,
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
    ): ActivitySession {
        return ActivitySession::create(
            startedAt: $this->instant($startedAt),
            finishedAt: $this->instant($finishedAt),
            timerDuration: Duration::fromMicroseconds(
                $timerMicroseconds,
            ),
            timelineResolution: $timelineResolution,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
