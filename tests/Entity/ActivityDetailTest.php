<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\SequentialActivityDetail;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotRecordActivityDetail;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivityDetailTest extends TestCase
{
    public function testRecordsContiguousSequentialDetails(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:30:25Z',
                sequenceName: 'pool_length',
            ),
        );

        $detail = $this->sequentialDetail(
            startedAt: '2026-01-15T10:30:25Z',
            finishedAt: '2026-01-15T10:30:35Z',
            sequenceName: 'pool_length',
        );

        $activity->recordDetail($detail);

        self::assertTrue(
            $activity->lastDetailFinishedAt?->equals(
                $detail->interval()->finishedAt,
            ),
        );
    }

    public function testAllowsSubResolutionOverlapInSameSequence(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:30:25.375000Z',
                sequenceName: 'pool_length',
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $detail = $this->sequentialDetail(
            startedAt: '2026-01-15T10:30:25Z',
            finishedAt: '2026-01-15T10:30:50Z',
            sequenceName: 'pool_length',
            timelineResolution: TemporalResolution::Second,
        );

        $activity->recordDetail($detail);

        self::assertTrue(
            $activity->lastDetailFinishedAt?->equals(
                $detail->interval()->finishedAt,
            ),
        );
    }

    public function testRejectsOverlappingDetailsInSameSequence(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:30:25Z',
                sequenceName: 'pool_length',
                timelineResolution: TemporalResolution::Second,
            ),
        );

        $this->expectException(
            CannotRecordActivityDetail::class,
        );

        $this->expectExceptionMessage(
            'sequence pool_length cannot overlap',
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:24Z',
                finishedAt: '2026-01-15T10:30:50Z',
                sequenceName: 'pool_length',
                timelineResolution: TemporalResolution::Second,
            ),
        );
    }

    public function testAllowsOverlappingDetailsFromDifferentSequences(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:30:25Z',
                sequenceName: 'pool_length',
            ),
        );

        $detail = $this->sequentialDetail(
            startedAt: '2026-01-15T10:30:10Z',
            finishedAt: '2026-01-15T10:30:30Z',
            sequenceName: 'segment_effort',
        );

        $activity->recordDetail($detail);

        self::assertTrue(
            $activity->lastDetailFinishedAt?->equals(
                $detail->interval()->finishedAt,
            ),
        );
    }

    public function testRejectsInvalidSequentialDetailName(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $this->expectException(
            CannotRecordActivityDetail::class,
        );

        $this->expectExceptionMessage(
            'canonical snake_case',
        );

        $activity->recordDetail(
            $this->sequentialDetail(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:30:25Z',
                sequenceName: 'Pool Length',
            ),
        );
    }

    public function testCanRecordRetrospectiveDetailAfterFinish(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $detail = $this->detail(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:30:25Z',
        );

        $activity->recordDetail($detail);

        self::assertTrue(
            $activity->lastDetailFinishedAt?->equals(
                $detail->interval()->finishedAt,
            ),
        );
    }

    public function testRejectsDetailFinishingAfterActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->finish(
            $this->instant('2026-01-15T11:00:00Z'),
        );

        $this->expectException(
            CannotRecordActivityDetail::class,
        );

        $activity->recordDetail(
            $this->detail(
                startedAt: '2026-01-15T10:59:50Z',
                finishedAt: '2026-01-15T11:00:01Z',
            ),
        );
    }

    private function detail(
        string $startedAt,
        string $finishedAt,
    ): ActivityDetail {
        $interval = $this->interval(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
        );

        return new readonly class($interval) implements ActivityDetail {
            public function __construct(
                private ActivityInterval $interval,
            ) {
            }

            public function interval(): ActivityInterval
            {
                return $this->interval;
            }
        };
    }

    private function sequentialDetail(
        string $startedAt,
        string $finishedAt,
        string $sequenceName,
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
    ): SequentialActivityDetail {
        $interval = $this->interval(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
        );

        return new readonly class($interval, $sequenceName, $timelineResolution) implements SequentialActivityDetail {
            public function __construct(
                private ActivityInterval $interval,
                private string $sequenceName,
                private TemporalResolution $timelineResolution,
            ) {
            }

            public function interval(): ActivityInterval
            {
                return $this->interval;
            }

            public function sequenceName(): string
            {
                return $this->sequenceName;
            }

            public function timelineResolution(): TemporalResolution
            {
                return $this->timelineResolution;
            }
        };
    }

    private function interval(
        string $startedAt,
        string $finishedAt,
    ): ActivityInterval {
        $start = $this->instant($startedAt);
        $finish = $this->instant($finishedAt);

        return ActivityInterval::create(
            startedAt: $start,
            finishedAt: $finish,
            timerDuration: Duration::between(
                $start,
                $finish,
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
