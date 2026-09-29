<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Activity\Exception\CannotRecordObservation;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityObservationRecordingTest extends TestCase
{
    public function testStartedActivityHasNoLastObservationTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        self::assertNull(
            $activity->lastObservationAt,
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    public function testCanRecordObservation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $observation = $this->observation(
            '2026-01-15T10:31:00Z',
        );

        $activity->recordObservation($observation);

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $observation->timestamp,
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

    public function testCanRecordObservationAtActivityStart(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:45Z',
        );

        $activity = Activity::start($startedAt);

        $activity->recordObservation(
            ActivityObservation::create(
                $startedAt,
                $this->positionMeasurement(),
            ),
        );

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $startedAt,
            ),
        );
    }

    public function testCannotRecordObservationBeforeActivityStart(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $this->expectException(
            CannotRecordObservation::class,
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:30:44Z',
            ),
        );
    }

    public function testObservationsMustBeChronological(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:31:00Z',
            ),
        );

        $this->expectException(
            CannotRecordObservation::class,
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T10:30:59Z',
            ),
        );
    }

    public function testRejectedObservationDoesNotChangeState(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $accepted = $this->observation(
            '2026-01-15T10:31:00Z',
        );

        $activity->recordObservation($accepted);

        try {
            $activity->recordObservation(
                $this->observation(
                    '2026-01-15T10:30:59Z',
                ),
            );

            self::fail(
                'Expected observation to be rejected.',
            );
        } catch (CannotRecordObservation) {
        }

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $accepted->timestamp,
            ),
        );
    }

    public function testCanRecordObservationsAtSameTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $timestamp = '2026-01-15T10:31:00Z';

        $activity->recordObservation(
            $this->observation($timestamp),
        );

        $activity->recordObservation(
            $this->observation($timestamp),
        );

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $this->instant($timestamp),
            ),
        );
    }

    public function testCannotRecordObservationAfterActivityFinished(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $this->expectException(
            CannotRecordObservation::class,
        );

        $activity->recordObservation(
            $this->observation(
                '2026-01-15T11:00:01Z',
            ),
        );
    }

    public function testCannotFinishActivityBeforeLastObservation(): void
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
            CannotFinishActivity::class,
        );

        $activity->finish(
            $this->instant('2026-01-15T10:44:59Z'),
        );
    }

    public function testCanFinishActivityAtLastObservationTimestamp(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:45Z'),
        );

        $finishedAt = $this->instant(
            '2026-01-15T10:45:00Z',
        );

        $activity->recordObservation(
            ActivityObservation::create(
                $finishedAt,
                $this->positionMeasurement(),
            ),
        );

        $activity->finish($finishedAt);

        self::assertTrue(
            $activity->finishedAt?->equals(
                $finishedAt,
            ),
        );
    }
}
