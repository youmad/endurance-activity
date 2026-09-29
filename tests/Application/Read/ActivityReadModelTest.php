<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Read;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Read\ActivityDistance;
use Youmad\Endurance\Activity\Application\Read\ActivityReadModel;
use Youmad\Endurance\Activity\Application\Read\ActivitySessionReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivityReadModelTest extends TestCase
{
    public function testExposesOrderedSessionsAndDurations(): void
    {
        $session = new ActivitySessionReadModel(
            index: 0,
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            timerDuration: Duration::fromMicroseconds(
                3_420_000_000,
            ),
            sport: 'cycling',
            subSport: 'road',
            startPosition: null,
            endPosition: null,
            distance: new ActivityDistance(28_754.3, 'm'),
        );
        $activity = new ActivityReadModel(
            id: ActivityId::generate(),
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            type: 'cycling',
            timerDuration: Duration::fromMicroseconds(
                3_420_000_000,
            ),
            pausedDuration: Duration::fromMicroseconds(
                180_000_000,
            ),
            distance: new ActivityDistance(28_754.3, 'm'),
            sessions: [$session],
        );

        self::assertSame(1, $activity->sessionCount());
        self::assertSame(
            3_600_000_000,
            $activity->elapsedDuration()?->toMicroseconds(),
        );
        self::assertSame(
            3_600_000_000,
            $session->elapsedDuration()->toMicroseconds(),
        );
        self::assertSame([$session], $activity->sessions());
    }

    public function testAcceptsSessionOverlapWithinDeclaredTimelineResolution(): void
    {
        $first = new ActivitySessionReadModel(
            index: 0,
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T10:30:00.595000Z'),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: 'running',
            subSport: 'generic',
            startPosition: null,
            endPosition: null,
            distance: null,
            timelineResolution: TemporalResolution::Second,
        );
        $second = new ActivitySessionReadModel(
            index: 1,
            startedAt: $this->instant('2026-08-05T10:30:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: 'running',
            subSport: 'generic',
            startPosition: null,
            endPosition: null,
            distance: null,
            timelineResolution: TemporalResolution::Second,
        );

        $activity = new ActivityReadModel(
            id: ActivityId::generate(),
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            type: 'running',
            timerDuration: Duration::fromMicroseconds(
                3_400_000_000,
            ),
            pausedDuration: Duration::fromMicroseconds(
                200_000_000,
            ),
            distance: null,
            sessions: [$first, $second],
        );

        self::assertSame(2, $activity->sessionCount());
    }

    public function testAcceptsSessionOverlapWithinGarminWholeSecondTolerance(): void
    {
        $first = new ActivitySessionReadModel(
            index: 0,
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant(
                '2026-08-05T10:30:00.504000Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: 'running',
            subSport: 'generic',
            startPosition: null,
            endPosition: null,
            distance: null,
            timelineResolution: TemporalResolution::Second,
        );
        $second = new ActivitySessionReadModel(
            index: 1,
            startedAt: $this->instant('2026-08-05T10:29:59Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: 'running',
            subSport: 'generic',
            startPosition: null,
            endPosition: null,
            distance: null,
            timelineResolution: TemporalResolution::Second,
        );

        $activity = new ActivityReadModel(
            id: ActivityId::generate(),
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            type: 'running',
            timerDuration: Duration::fromMicroseconds(
                3_400_000_000,
            ),
            pausedDuration: Duration::fromMicroseconds(
                200_000_000,
            ),
            distance: null,
            sessions: [$first, $second],
        );

        self::assertSame(2, $activity->sessionCount());
    }

    public function testRejectsSessionOverlapAtDefaultMicrosecondResolution(): void
    {
        $first = new ActivitySessionReadModel(
            index: 0,
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T10:30:00.000001Z'),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: null,
            subSport: null,
            startPosition: null,
            endPosition: null,
            distance: null,
        );
        $second = new ActivitySessionReadModel(
            index: 1,
            startedAt: $this->instant('2026-08-05T10:30:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: null,
            subSport: null,
            startPosition: null,
            endPosition: null,
            distance: null,
        );

        $this->expectException(\InvalidArgumentException::class);

        new ActivityReadModel(
            id: ActivityId::generate(),
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            type: null,
            timerDuration: Duration::fromMicroseconds(
                3_400_000_000,
            ),
            pausedDuration: Duration::fromMicroseconds(
                200_000_000,
            ),
            distance: null,
            sessions: [$first, $second],
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
