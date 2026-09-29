<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\Port\LapWriter;
use Youmad\Endurance\Activity\Application\UseCase\RecordLap;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotRecordLap;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class RecordLapTest extends TestCase
{
    private ActivityRepository&MockObject $activities;

    private LapWriter&MockObject $laps;

    private ActivityTransaction $transaction;

    public function testRecordsLapAndPersistsActivityInsideTransaction(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $lap = $this->lap(
            startedAt: '2026-01-15T10:30:00Z',
            finishedAt: '2026-01-15T10:45:00Z',
        );

        $this->activities
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

        $this->laps
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($activityId): bool => $activity
                        ->id
                        ->equals($activityId),
                ),
                $lap,
            );

        $this->activities
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
                static fn (
                    \Closure $operation,
                ): mixed => $operation(),
            );

        $this->transaction = $transaction;

        $this->useCase()->handle(
            activityId: $activity->id,
            lap: $lap,
        );

        self::assertTrue(
            $activity->lastLapFinishedAt?->equals(
                $lap->finishedAt,
            ),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    private function lap(
        string $startedAt,
        string $finishedAt,
    ): Lap {
        $startedAt = $this->instant($startedAt);
        $finishedAt = $this->instant($finishedAt);

        return Lap::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
        );
    }

    private function useCase(): RecordLap
    {
        return new RecordLap(
            activities: $this->activities,
            laps: $this->laps,
            transaction: $this->transaction,
        );
    }

    public function testDoesNotWriteRejectedLap(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $activity->recordLap(
            $this->lap(
                startedAt: '2026-01-15T10:30:00Z',
                finishedAt: '2026-01-15T10:45:00Z',
            ),
        );

        $overlappingLap = $this->lap(
            startedAt: '2026-01-15T10:44:00Z',
            finishedAt: '2026-01-15T11:00:00Z',
        );

        $this->activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);

        $this->laps
            ->expects(self::never())
            ->method('append');

        $this->activities
            ->expects(self::never())
            ->method('save');

        $this->expectException(
            CannotRecordLap::class,
        );

        $this->useCase()->handle(
            activityId: $activity->id,
            lap: $overlappingLap,
        );
    }

    protected function setUp(): void
    {
        $this->activities = $this->createMock(
            ActivityRepository::class,
        );

        $this->laps = $this->createMock(
            LapWriter::class,
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
