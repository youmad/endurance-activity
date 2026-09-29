<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityDetailWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\RecordActivityDetail;
use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class RecordActivityDetailTest extends TestCase
{
    private ActivityRepository&MockObject $activities;

    private ActivityDetailWriter&MockObject $details;

    private ActivityTransaction $transaction;

    public function testRecordsDetailAndPersistsActivityInsideTransaction(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $detail = $this->detail();

        $this->activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);

        $this->details
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($activityId): bool => $activity
                        ->id
                        ->equals($activityId),
                ),
                $detail,
            );

        $this->activities
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        $this->useCase()->handle(
            activityId: $activity->id,
            detail: $detail,
        );

        self::assertTrue(
            $activity->lastDetailFinishedAt?->equals(
                $detail->interval()->finishedAt,
            ),
        );
    }

    private function useCase(): RecordActivityDetail
    {
        return new RecordActivityDetail(
            activities: $this->activities,
            details: $this->details,
            transaction: $this->transaction,
        );
    }

    private function detail(): ActivityDetail
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );
        $finishedAt = $this->instant(
            '2026-01-15T10:30:25Z',
        );
        $interval = ActivityInterval::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
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

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    protected function setUp(): void
    {
        $this->activities = $this->createMock(
            ActivityRepository::class,
        );

        $this->details = $this->createMock(
            ActivityDetailWriter::class,
        );

        $this->transaction = $this->createStub(
            ActivityTransaction::class,
        );

        $this->transaction
            ->method('run')
            ->willReturnCallback(
                static fn (
                    \Closure $operation,
                ): mixed => $operation(),
            );
    }
}
