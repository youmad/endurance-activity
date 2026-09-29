<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivitySessionWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\RecordActivitySession;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class RecordActivitySessionTest extends TestCase
{
    private ActivityRepository&MockObject $activities;
    private ActivitySessionWriter&MockObject $sessions;
    private ActivityTransaction $transaction;

    public function testRecordsSessionAndPersistsActivityInsideTransaction(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $session = $this->session(
            '2026-01-15T10:30:00Z',
            '2026-01-15T11:00:00Z',
        );

        $this->activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);

        $this->sessions
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($id): bool => $activity
                        ->id
                        ->equals($id),
                ),
                $session,
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
                static fn (\Closure $operation): mixed => $operation(),
            );
        $this->transaction = $transaction;

        $this->useCase()->handle(
            activityId: $activity->id,
            session: $session,
        );

        self::assertTrue(
            $activity->lastSessionFinishedAt?->equals(
                $session->finishedAt,
            ),
        );
    }

    protected function setUp(): void
    {
        $this->activities = $this->createMock(
            ActivityRepository::class,
        );
        $this->sessions = $this->createMock(
            ActivitySessionWriter::class,
        );
        $this->transaction = $this->createStub(
            ActivityTransaction::class,
        );
        $this->transaction
            ->method('run')
            ->willReturnCallback(
                static fn (\Closure $operation): mixed => $operation(),
            );
    }

    private function useCase(): RecordActivitySession
    {
        return new RecordActivitySession(
            activities: $this->activities,
            sessions: $this->sessions,
            transaction: $this->transaction,
        );
    }

    private function session(
        string $startedAt,
        string $finishedAt,
    ): ActivitySession {
        $startedAt = $this->instant($startedAt);
        $finishedAt = $this->instant($finishedAt);

        return ActivitySession::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
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
