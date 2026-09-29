<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotRecordActivitySession;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivitySessionRecordingTest extends TestCase
{
    public function testRecordsSequentialRetrospectiveSessions(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordObservation(
            ActivityObservation::create(
                $this->instant('2026-01-15T11:00:00Z'),
                new ScalarMeasurement(
                    measurementType: MeasurementType::fromString(
                        'heart_rate',
                    ),
                    value: 150,
                    unit: MeasurementUnit::fromSymbol('bpm'),
                ),
            ),
        );

        $first = $this->session(
            startedAt: '2026-01-15T10:00:00Z',
            finishedAt: '2026-01-15T10:30:00Z',
        );
        $second = $this->session(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T11:00:00Z',
        );

        $activity->recordSession($first);
        $activity->recordSession($second);

        self::assertTrue(
            $activity->lastSessionFinishedAt?->equals(
                $second->finishedAt,
            ),
        );
    }

    public function testAcceptsSessionOverlapWithinDeclaredTimelineResolution(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00.595000Z',
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $second = $this->session(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T11:00:00Z',
            timelineResolution: TemporalResolution::Second,
        );

        $activity->recordSession($second);

        self::assertTrue(
            $activity->lastSessionFinishedAt?->equals(
                $second->finishedAt,
            ),
        );

        $snapshot = $activity->snapshot();

        self::assertTrue(
            $snapshot->lastSessionTimelineFinishedAt?->equals(
                $this->instant(
                    '2026-01-15T11:00:00.595000Z',
                ),
            ),
        );
        self::assertSame(
            3_600_595_000,
            $snapshot->recordedSessionTimerDuration
                ->toMicroseconds(),
        );
    }

    public function testAcceptsSessionOverlapWithinGarminWholeSecondTolerance(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00.504000Z',
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $second = $this->session(
            startedAt: '2026-01-15T10:29:59Z',
            finishedAt: '2026-01-15T11:00:00Z',
            timelineResolution: TemporalResolution::Second,
        );

        $activity->recordSession($second);

        self::assertTrue(
            $activity->lastSessionFinishedAt?->equals(
                $second->finishedAt,
            ),
        );
    }

    public function testRejectsOverlappingSessions(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:00:00Z',
                finishedAt: '2026-01-15T10:30:00Z',
            ),
        );

        $this->expectException(
            CannotRecordActivitySession::class,
        );

        $activity->recordSession(
            $this->session(
                startedAt: '2026-01-15T10:29:00Z',
                finishedAt: '2026-01-15T11:00:00Z',
            ),
        );
    }

    private function session(
        string $startedAt,
        string $finishedAt,
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
    ): ActivitySession {
        $startedAt = $this->instant($startedAt);
        $finishedAt = $this->instant($finishedAt);

        return ActivitySession::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
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
