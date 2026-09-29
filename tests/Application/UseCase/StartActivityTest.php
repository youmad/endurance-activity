<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\StartActivity;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class StartActivityTest extends TestCase
{
    public function testStartsAndPersistsActivityAtomically(): void
    {
        $startedAt = Instant::fromDateTimeImmutable(
            new \DateTimeImmutable('2026-01-15T10:30:00Z'),
        );
        $savedActivity = null;

        $activities = $this->createMock(ActivityRepository::class);
        $activities
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(
                static function (Activity $activity) use (
                    &$savedActivity,
                ): void {
                    $savedActivity = $activity;
                },
            );

        $transaction = $this->createMock(ActivityTransaction::class);
        $transaction
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(
                static fn (\Closure $operation): mixed => $operation(),
            );

        $activityId = (new StartActivity(
            activities: $activities,
            transaction: $transaction,
        ))->handle($startedAt);

        self::assertInstanceOf(Activity::class, $savedActivity);
        self::assertTrue($activityId->equals($savedActivity->id));
        self::assertTrue($startedAt->equals($savedActivity->startedAt));
    }
}
