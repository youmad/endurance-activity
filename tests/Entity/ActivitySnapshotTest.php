<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\Pool\PoolLength;
use Youmad\Endurance\Activity\Detail\Pool\PoolLengthType;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivitySnapshotTest extends TestCase
{
    public function testRestoresCompleteAggregateState(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );
        $activity->pause(
            $this->instant('2026-01-15T10:05:00Z'),
        );
        $activity->resume(
            $this->instant('2026-01-15T10:06:00Z'),
        );
        $activity->recordObservation(
            ActivityObservation::create(
                timestamp: $this->instant(
                    '2026-01-15T10:10:00Z',
                ),
                measurements: new ScalarMeasurement(
                    measurementType: MeasurementType::fromString('heart_rate'),
                    value: 150,
                    unit: MeasurementUnit::fromSymbol('bpm'),
                ),
            ),
        );
        $activity->recordLap(
            Lap::create(
                startedAt: $this->instant(
                    '2026-01-15T10:00:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:20:00Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_140_000_000,
                ),
            ),
        );
        $activity->recordDetail(
            PoolLength::create(
                interval: ActivityInterval::create(
                    startedAt: $this->instant(
                        '2026-01-15T10:20:00Z',
                    ),
                    finishedAt: $this->instant(
                        '2026-01-15T10:25:00Z',
                    ),
                    timerDuration: Duration::fromMicroseconds(
                        300_000_000,
                    ),
                ),
                type: PoolLengthType::Active,
                stroke: 'freestyle',
            ),
        );
        $activity->recordSession(
            ActivitySession::create(
                startedAt: $this->instant(
                    '2026-01-15T10:00:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_740_000_000,
                ),
            ),
        );
        $activity->applySummary(
            ActivitySummary::create(
                reportedAt: $this->instant(
                    '2026-01-15T10:30:02Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_740_000_000,
                ),
                sessionCount: 1,
                type: 'pool_swimming',
            ),
        );

        $snapshot = $activity->snapshot();
        $restored = Activity::restore($snapshot);
        $restoredSnapshot = $restored->snapshot();

        self::assertTrue($activity->id->equals($restored->id));
        self::assertTrue(
            $snapshot->startedAt->equals(
                $restoredSnapshot->startedAt,
            ),
        );
        self::assertTrue(
            $snapshot->finishedAt?->equals(
                $restoredSnapshot->finishedAt,
            ),
        );
        self::assertSame(
            $snapshot->sessionCount,
            $restoredSnapshot->sessionCount,
        );
        self::assertSame(
            $snapshot->recordedSessionTimerDuration
                ->toMicroseconds(),
            $restoredSnapshot->recordedSessionTimerDuration
                ->toMicroseconds(),
        );
        self::assertTrue(
            $snapshot->lastSessionTimelineFinishedAt?->equals(
                $restoredSnapshot->lastSessionTimelineFinishedAt,
            ),
        );
        self::assertSame(
            $snapshot->accumulatedPausedDuration
                ->toMicroseconds(),
            $restoredSnapshot->accumulatedPausedDuration
                ->toMicroseconds(),
        );
        self::assertSame(
            $snapshot->type,
            $restoredSnapshot->type,
        );
        self::assertSame(
            array_keys(
                $snapshot->lastSequentialDetailFinishedAt,
            ),
            array_keys(
                $restoredSnapshot->lastSequentialDetailFinishedAt,
            ),
        );
        self::assertTrue(
            $snapshot->lastSequentialDetailFinishedAt['pool_length']
                ->equals(
                    $restoredSnapshot
                        ->lastSequentialDetailFinishedAt['pool_length'],
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
