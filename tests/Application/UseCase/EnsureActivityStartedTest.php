<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\EnsureActivityStarted;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotConfirmActivityStart;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class EnsureActivityStartedTest extends TestCase
{
    public function testCreatesActivityWithCallerProvidedId(): void
    {
        $activityId = ActivityId::generate();
        $startedAt = $this->instant('2026-01-15T10:30:00Z');
        $created = null;

        $activities = $this->createMock(ActivityRepository::class);
        $activities
            ->expects(self::once())
            ->method('createIfAbsent')
            ->willReturnCallback(
                static function (Activity $activity) use (&$created): bool {
                    $created = $activity;

                    return true;
                },
            );
        $activities->expects(self::never())->method('get');

        (new EnsureActivityStarted(
            activities: $activities,
            transaction: $this->transaction(),
        ))->handle(
            activityId: $activityId,
            startedAt: $startedAt,
        );

        self::assertInstanceOf(Activity::class, $created);
        self::assertTrue($activityId->equals($created->id));
        self::assertTrue($startedAt->equals($created->startedAt));
    }

    public function testAcceptsRetryForExistingMatchingActivity(): void
    {
        $activityId = ActivityId::generate();
        $startedAt = $this->instant('2026-01-15T10:30:00Z');
        $existing = Activity::startWithId($activityId, $startedAt);

        $activities = $this->createMock(ActivityRepository::class);
        $activities
            ->expects(self::once())
            ->method('createIfAbsent')
            ->willReturn(false);
        $activities
            ->expects(self::once())
            ->method('get')
            ->with($activityId)
            ->willReturn($existing);

        (new EnsureActivityStarted(
            activities: $activities,
            transaction: $this->transaction(),
        ))->handle(
            activityId: $activityId,
            startedAt: $startedAt,
        );
    }

    public function testRejectsRetryWithDifferentStartTime(): void
    {
        $activityId = ActivityId::generate();
        $existing = Activity::startWithId(
            $activityId,
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activities = $this->createStub(ActivityRepository::class);
        $activities->method('createIfAbsent')->willReturn(false);
        $activities->method('get')->willReturn($existing);

        $this->expectException(CannotConfirmActivityStart::class);

        (new EnsureActivityStarted(
            activities: $activities,
            transaction: $this->transaction(),
        ))->handle(
            activityId: $activityId,
            startedAt: $this->instant('2026-01-15T10:31:00Z'),
        );
    }

    private function transaction(): ActivityTransaction
    {
        $transaction = $this->createStub(ActivityTransaction::class);
        $transaction
            ->method('run')
            ->willReturnCallback(
                static fn (\Closure $operation): mixed => $operation(),
            );

        return $transaction;
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
