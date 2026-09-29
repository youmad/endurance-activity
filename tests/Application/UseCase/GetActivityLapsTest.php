<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityLapReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityLapsReadModel;
use Youmad\Endurance\Activity\Application\UseCase\GetActivityLaps;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class GetActivityLapsTest extends TestCase
{
    public function testDelegatesReadToRepository(): void
    {
        $id = ActivityId::generate();
        $laps = new ActivityLapsReadModel(
            activityId: $id,
            laps: [],
        );
        $repository = $this->createMock(
            ActivityLapReadRepository::class,
        );
        $repository
            ->expects(self::once())
            ->method('findForActivity')
            ->with(self::identicalTo($id))
            ->willReturn($laps);

        self::assertSame(
            $laps,
            (new GetActivityLaps($repository))->find($id),
        );
    }
}
