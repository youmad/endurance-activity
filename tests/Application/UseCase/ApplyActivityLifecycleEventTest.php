<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityLifecycleAction;
use Youmad\Endurance\Activity\Application\Import\ActivityLifecycleItem;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\ApplyActivityLifecycleEvent;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ApplyActivityLifecycleEventTest extends TestCase
{
    public function testAppliesTimerLifecycleInOrder(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activities = $this->createMock(
            ActivityRepository::class,
        );

        $activities
            ->expects(self::exactly(4))
            ->method('get')
            ->willReturn($activity);

        $activities
            ->expects(self::exactly(3))
            ->method('save')
            ->with($activity);

        $transaction = $this->createStub(
            ActivityTransaction::class,
        );

        $transaction
            ->method('run')
            ->willReturnCallback(
                static fn (
                    \Closure $operation,
                ): mixed => $operation(),
            );

        $useCase = new ApplyActivityLifecycleEvent(
            activities: $activities,
            transaction: $transaction,
        );

        foreach (
            [
                [
                    ActivityLifecycleAction::Start,
                    '2026-01-15T10:30:00Z',
                ],
                [
                    ActivityLifecycleAction::Pause,
                    '2026-01-15T10:45:00Z',
                ],
                [
                    ActivityLifecycleAction::Resume,
                    '2026-01-15T10:50:00Z',
                ],
                [
                    ActivityLifecycleAction::Finish,
                    '2026-01-15T11:00:00Z',
                ],
            ] as [$action, $occurredAt]
        ) {
            $useCase->handle(
                activityId: $activity->id,
                event: new ActivityLifecycleItem(
                    action: $action,
                    occurredAt: $this->instant(
                        $occurredAt,
                    ),
                ),
            );
        }

        self::assertFalse($activity->isPaused());

        self::assertSame(
            300_000_000,
            $activity
                ->accumulatedPausedDuration
                ->toMicroseconds(),
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2026-01-15T11:00:00Z',
                ),
            ),
        );
    }

    public function testInitialTimerStartIsPersistedSeparately(): void
    {
        $activity = Activity::start($this->instant('2026-01-15T10:30:00Z'));
        $activities = $this->createMock(ActivityRepository::class);
        $activities->expects(self::once())->method('get')->willReturn($activity);
        $activities->expects(self::once())->method('save')->with($activity);
        $transaction = $this->createStub(ActivityTransaction::class);
        $transaction->method('run')->willReturnCallback(static fn (\Closure $operation): mixed => $operation());
        (new ApplyActivityLifecycleEvent($activities, $transaction))->handle(
            $activity->id,
            new ActivityLifecycleItem(ActivityLifecycleAction::TimerStart, $this->instant('2026-01-15T10:30:03Z')),
        );
        self::assertTrue($activity->startedAt->equals($this->instant('2026-01-15T10:30:00Z')));
        self::assertTrue($activity->timerStartedAt?->equals($this->instant('2026-01-15T10:30:03Z')));
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
