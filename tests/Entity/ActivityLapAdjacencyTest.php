<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Read\ActivityLapReadModel;
use Youmad\Endurance\Activity\Application\Read\ActivityLapsReadModel;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotRecordLap;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivityLapAdjacencyTest extends TestCase
{
    /** @return iterable<string, array{string, string, TemporalResolution, bool}> */
    public static function boundaries(): iterable
    {
        yield 'Fr935 one-second overlap' => ['44', '43', TemporalResolution::Second, true];
        yield 'Epix 1.713-second overlap' => ['54.713', '53', TemporalResolution::Second, true];
        yield 'two-second overlap inclusive' => ['32', '30', TemporalResolution::Second, true];
        yield '2.999-second overlap floors previous end' => ['32.999', '30', TemporalResolution::Second, true];
        yield 'three-second overlap rejected' => ['33', '30', TemporalResolution::Second, false];
        yield 'contiguous laps' => ['30', '30', TemporalResolution::Second, true];
        yield 'two-second gap inclusive' => ['30', '32', TemporalResolution::Second, true];
        yield 'three-second gap rejected' => ['30', '33', TemporalResolution::Second, false];
        yield '2.001-second gap has three-second SDK delta' => ['30.999', '33', TemporalResolution::Second, false];
        yield 'precise overlap rejected' => ['30.000001', '30', TemporalResolution::Microsecond, false];
        yield 'precise contiguous laps' => ['30', '30', TemporalResolution::Microsecond, true];
        yield 'precise source permits gaps' => ['30', '33', TemporalResolution::Microsecond, true];
    }

    #[DataProvider('boundaries')]
    public function testRecordingUsesSourceAdjacencyRule(
        string $previousEnd,
        string $nextStart,
        TemporalResolution $resolution,
        bool $accepted,
    ): void {
        $activity = Activity::start($this->instant('10:00:00'));
        $first = Lap::create(
            startedAt: $this->instant('10:00:00'),
            finishedAt: $this->instant('10:30:'.$previousEnd),
            timerDuration: Duration::zero(),
            timelineResolution: $resolution,
        );
        $second = Lap::create(
            startedAt: $this->instant('10:30:'.$nextStart),
            finishedAt: $this->instant('11:00:00.123'),
            timerDuration: Duration::fromMicroseconds(1_000_001),
            timelineResolution: $resolution,
        );
        $activity->recordLap($first);

        if (!$accepted) {
            $this->expectException(CannotRecordLap::class);
        }

        $activity->recordLap($second);

        self::assertTrue($activity->lastLapFinishedAt?->equals($second->finishedAt));
        self::assertTrue($second->startedAt->equals($this->instant('10:30:'.$nextStart)));
        self::assertSame(1_000_001, $second->timerDuration->toMicroseconds());
    }

    #[DataProvider('boundaries')]
    public function testReadModelUsesSameSourceAdjacencyRule(
        string $previousEnd,
        string $nextStart,
        TemporalResolution $resolution,
        bool $accepted,
    ): void {
        $first = new ActivityLapReadModel(
            index: 0,
            startedAt: $this->instant('10:00:00'),
            finishedAt: $this->instant('10:30:'.$previousEnd),
            timerDuration: Duration::zero(),
            distance: null,
            timelineResolution: $resolution,
        );
        $second = new ActivityLapReadModel(
            index: 1,
            startedAt: $this->instant('10:30:'.$nextStart),
            finishedAt: $this->instant('11:00:00.123'),
            timerDuration: Duration::fromMicroseconds(1_000_001),
            distance: null,
            timelineResolution: $resolution,
        );

        if (!$accepted) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $readModel = new ActivityLapsReadModel(ActivityId::generate(), [$first, $second]);

        self::assertSame([$first, $second], $readModel->laps());
        self::assertSame(1_000_001, $readModel->laps()[1]->timerDuration->toMicroseconds());
    }

    private function instant(string $time): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable('2026-01-15T'.$time.'Z'),
        );
    }
}
