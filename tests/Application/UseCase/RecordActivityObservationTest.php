<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityObservationWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\RecordActivityObservation;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\CannotRecordObservation;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class RecordActivityObservationTest extends TestCase
{
    private ActivityRepository&MockObject $activities;

    private ActivityObservationWriter&MockObject $observations;

    private ActivityTransaction $transaction;

    public function testRecordsObservationAndPersistsActivity(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $observation = $this->observation(
            '2026-01-15T10:30:01Z',
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

        $this->observations
            ->expects(self::once())
            ->method('append')
            ->with(
                self::callback(
                    static fn ($activityId): bool => $activity
                        ->id
                        ->equals($activityId),
                ),
                $observation,
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
            observation: $observation,
        );

        self::assertTrue(
            $activity->lastObservationAt?->equals(
                $observation->timestamp,
            ),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }

    private function observation(
        string $timestamp,
    ): ActivityObservation {
        return ActivityObservation::create(
            $this->instant($timestamp),
            new PositionMeasurement(
                new Coordinate(
                    latitude: 59.4369,
                    longitude: 24.7535,
                ),
            ),
        );
    }

    private function useCase(): RecordActivityObservation
    {
        return new RecordActivityObservation(
            activities: $this->activities,
            observations: $this->observations,
            transaction: $this->transaction,
        );
    }

    public function testDoesNotWriteRejectedObservation(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $acceptedObservation = $this->observation(
            '2026-01-15T10:30:10Z',
        );

        $activity->recordObservation(
            $acceptedObservation,
        );

        $rejectedObservation = $this->observation(
            '2026-01-15T10:30:09Z',
        );

        $this->activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);

        $this->observations
            ->expects(self::never())
            ->method('append');

        $this->activities
            ->expects(self::never())
            ->method('save');

        $this->expectException(
            CannotRecordObservation::class,
        );

        $this->useCase()->handle(
            activityId: $activity->id,
            observation: $rejectedObservation,
        );
    }

    public function testExecutesOperationInsideTransaction(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $observation = $this->observation(
            '2026-01-15T10:30:01Z',
        );

        $operationWasExecuted = false;

        $transaction = $this->createMock(
            ActivityTransaction::class,
        );

        $transaction
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(
                static function (
                    \Closure $operation,
                ) use (
                    &$operationWasExecuted,
                ): mixed {
                    $operationWasExecuted = true;

                    return $operation();
                },
            );

        $this->transaction = $transaction;

        $this->activities
            ->expects(self::once())
            ->method('get')
            ->willReturn($activity);

        $this->observations
            ->expects(self::once())
            ->method('append');

        $this->activities
            ->expects(self::once())
            ->method('save');

        $this->useCase()->handle(
            activityId: $activity->id,
            observation: $observation,
        );

        self::assertTrue(
            $operationWasExecuted,
        );
    }

    protected function setUp(): void
    {
        $this->activities = $this->createMock(
            ActivityRepository::class,
        );

        $this->observations = $this->createMock(
            ActivityObservationWriter::class,
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
