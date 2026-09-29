<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\ApplyActivitySummary;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ApplyActivitySummaryTest extends TestCase
{
    public function testAppliesSummaryTransactionally(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );

        $activity->recordSession(
            ActivitySession::create(
                startedAt: $this->instant(
                    '2026-01-15T10:00:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_700_000_000,
                ),
            ),
        );

        $summary = ActivitySummary::create(
            reportedAt: $this->instant(
                '2026-01-15T10:30:01Z',
            ),
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sessionCount: 1,
            type: 'manual',
        );

        $activities = $this->createMock(
            ActivityRepository::class,
        );

        $activities
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(
                    static fn ($activityId): bool => $activity
                        ->id
                        ->equals($activityId),
                ),
            )
            ->willReturn($activity);

        $activities
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        $transaction = $this->createMock(
            ActivityTransaction::class,
        );

        $transaction
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(
                static fn (\Closure $operation): mixed => $operation(),
            );

        (new ApplyActivitySummary(
            activities: $activities,
            transaction: $transaction,
        ))->handle(
            activityId: $activity->id,
            summary: $summary,
        );

        self::assertSame(
            'manual',
            $activity->type,
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
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
