<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Read;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Read\ActivityLapReadModel;
use Youmad\Endurance\Activity\Application\Read\ActivityLapsReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivityLapsReadModelTest extends TestCase
{
    public function testPreservesOrderedNonOverlappingLaps(): void
    {
        $laps = new ActivityLapsReadModel(
            activityId: ActivityId::generate(),
            laps: [
                $this->lap(
                    index: 0,
                    startedAt: '2026-08-05T10:00:00Z',
                    finishedAt: '2026-08-05T10:30:00Z',
                ),
                $this->lap(
                    index: 1,
                    startedAt: '2026-08-05T10:30:00Z',
                    finishedAt: '2026-08-05T11:00:00Z',
                ),
            ],
        );

        self::assertSame(2, $laps->count());
        self::assertSame(1, $laps->laps()[1]->index);
    }

    public function testRejectsDiscontinuousIndexes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Activity laps must use continuous zero-based indexes.',
        );

        new ActivityLapsReadModel(
            activityId: ActivityId::generate(),
            laps: [
                $this->lap(
                    index: 1,
                    startedAt: '2026-08-05T10:00:00Z',
                    finishedAt: '2026-08-05T10:30:00Z',
                ),
            ],
        );
    }

    public function testRejectsOverlappingLaps(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Activity laps cannot overlap under the selected adjacency policy.',
        );

        new ActivityLapsReadModel(
            activityId: ActivityId::generate(),
            laps: [
                $this->lap(
                    index: 0,
                    startedAt: '2026-08-05T10:00:00Z',
                    finishedAt: '2026-08-05T10:30:00Z',
                ),
                $this->lap(
                    index: 1,
                    startedAt: '2026-08-05T10:29:59Z',
                    finishedAt: '2026-08-05T11:00:00Z',
                ),
            ],
        );
    }

    public function testAllowsOverlapSmallerThanLapTimelineResolution(): void
    {
        $laps = new ActivityLapsReadModel(
            activityId: ActivityId::generate(),
            laps: [
                $this->lap(
                    index: 0,
                    startedAt: '2026-08-05T10:00:00Z',
                    finishedAt: '2026-08-05T10:30:00.551000Z',
                ),
                $this->lap(
                    index: 1,
                    startedAt: '2026-08-05T10:30:00Z',
                    finishedAt: '2026-08-05T11:00:00Z',
                    timelineResolution: TemporalResolution::Second,
                ),
            ],
        );

        self::assertSame(2, $laps->count());
    }

    public function testRejectsOverlapBeyondTwoWholeSeconds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Activity laps must be sequential and abut within two whole seconds.',
        );

        new ActivityLapsReadModel(
            activityId: ActivityId::generate(),
            laps: [
                $this->lap(
                    index: 0,
                    startedAt: '2026-08-05T10:00:00Z',
                    finishedAt: '2026-08-05T10:30:03Z',
                ),
                $this->lap(
                    index: 1,
                    startedAt: '2026-08-05T10:30:00Z',
                    finishedAt: '2026-08-05T11:00:00Z',
                    timelineResolution: TemporalResolution::Second,
                ),
            ],
        );
    }

    private function lap(
        int $index,
        string $startedAt,
        string $finishedAt,
        TemporalResolution $timelineResolution =
            TemporalResolution::Microsecond,
    ): ActivityLapReadModel {
        return new ActivityLapReadModel(
            index: $index,
            startedAt: $this->instant($startedAt),
            finishedAt: $this->instant($finishedAt),
            timerDuration: Duration::zero(),
            distance: null,
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
