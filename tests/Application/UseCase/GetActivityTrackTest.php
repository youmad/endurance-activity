<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityTrackReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackPage;
use Youmad\Endurance\Activity\Application\UseCase\GetActivityTrack;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class GetActivityTrackTest extends TestCase
{
    public function testDelegatesPageReadToRepository(): void
    {
        $activityId = ActivityId::generate();
        $page = new ActivityTrackPage(
            activityId: $activityId,
            points: [],
            nextCursor: null,
        );
        $repository = $this->createMock(
            ActivityTrackReadRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('findPage')
            ->with(
                self::identicalTo($activityId),
                250,
                null,
            )
            ->willReturn($page);

        self::assertSame(
            $page,
            (new GetActivityTrack($repository))->page(
                activityId: $activityId,
                limit: 250,
            ),
        );
    }

    public function testRejectsLimitOutsideSafeRange(): void
    {
        $repository = $this->createStub(
            ActivityTrackReadRepository::class,
        );
        $track = new GetActivityTrack($repository);

        $this->expectException(\InvalidArgumentException::class);

        $track->page(
            activityId: ActivityId::generate(),
            limit: GetActivityTrack::MAX_LIMIT + 1,
        );
    }
}
