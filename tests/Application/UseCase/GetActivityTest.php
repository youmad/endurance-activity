<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityReadModel;
use Youmad\Endurance\Activity\Application\UseCase\GetActivity;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class GetActivityTest extends TestCase
{
    public function testDelegatesReadToRepository(): void
    {
        $id = ActivityId::generate();
        $activity = new ActivityReadModel(
            id: $id,
            startedAt: $this->instant('2026-08-05T10:00:00Z'),
            finishedAt: $this->instant('2026-08-05T11:00:00Z'),
            type: 'cycling',
            timerDuration: Duration::zero(),
            pausedDuration: Duration::zero(),
            distance: null,
            sessions: [],
        );
        $repository = $this->createMock(ActivityReadRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(self::identicalTo($id))
            ->willReturn($activity);

        self::assertSame(
            $activity,
            (new GetActivity($repository))->find($id),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
