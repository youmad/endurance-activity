<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Entity;

use Youmad\Endurance\Activity\Exception\InvalidActivitySnapshot;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivitySnapshot
{
    /**
     * @param array<string, Instant> $lastSequentialDetailFinishedAt
     */
    private function __construct(
        public ActivityId $id,
        public Instant $startedAt,
        public ?Instant $finishedAt,
        public ?Instant $lastObservationAt,
        public ?Instant $lastLapFinishedAt,
        public ?Instant $lastSessionFinishedAt,
        public ?Instant $lastSessionTimelineFinishedAt,
        public ?Instant $lastDetailFinishedAt,
        public array $lastSequentialDetailFinishedAt,
        public int $sessionCount,
        public Duration $recordedSessionTimerDuration,
        public ?Instant $summaryReportedAt,
        public ?string $type,
        public ?Instant $pausedAt,
        public Duration $accumulatedPausedDuration,
        public Instant $latestTimestamp,
        public ?int $localTimeOffsetSeconds = null,
        public ?Instant $timerStartedAt = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $lastSequentialDetailFinishedAt
     */
    public static function create(
        ActivityId $id,
        Instant $startedAt,
        ?Instant $finishedAt,
        ?Instant $lastObservationAt,
        ?Instant $lastLapFinishedAt,
        ?Instant $lastSessionFinishedAt,
        ?Instant $lastSessionTimelineFinishedAt,
        ?Instant $lastDetailFinishedAt,
        array $lastSequentialDetailFinishedAt,
        int $sessionCount,
        Duration $recordedSessionTimerDuration,
        ?Instant $summaryReportedAt,
        ?string $type,
        ?Instant $pausedAt,
        Duration $accumulatedPausedDuration,
        Instant $latestTimestamp,
        ?int $localTimeOffsetSeconds = null,
        ?Instant $timerStartedAt = null,
    ): self {
        self::assertState(
            startedAt: $startedAt,
            timerStartedAt: $timerStartedAt,
            finishedAt: $finishedAt,
            lastObservationAt: $lastObservationAt,
            lastLapFinishedAt: $lastLapFinishedAt,
            lastSessionFinishedAt: $lastSessionFinishedAt,
            lastSessionTimelineFinishedAt: $lastSessionTimelineFinishedAt,
            lastDetailFinishedAt: $lastDetailFinishedAt,
            lastSequentialDetailFinishedAt: $lastSequentialDetailFinishedAt,
            sessionCount: $sessionCount,
            recordedSessionTimerDuration: $recordedSessionTimerDuration,
            summaryReportedAt: $summaryReportedAt,
            type: $type,
            pausedAt: $pausedAt,
            accumulatedPausedDuration: $accumulatedPausedDuration,
            latestTimestamp: $latestTimestamp,
        );

        ksort($lastSequentialDetailFinishedAt);

        return new self(
            id: $id,
            startedAt: $startedAt,
            timerStartedAt: $timerStartedAt,
            finishedAt: $finishedAt,
            lastObservationAt: $lastObservationAt,
            lastLapFinishedAt: $lastLapFinishedAt,
            lastSessionFinishedAt: $lastSessionFinishedAt,
            lastSessionTimelineFinishedAt: $lastSessionTimelineFinishedAt,
            lastDetailFinishedAt: $lastDetailFinishedAt,
            lastSequentialDetailFinishedAt: $lastSequentialDetailFinishedAt,
            sessionCount: $sessionCount,
            recordedSessionTimerDuration: $recordedSessionTimerDuration,
            summaryReportedAt: $summaryReportedAt,
            type: $type,
            pausedAt: $pausedAt,
            accumulatedPausedDuration: $accumulatedPausedDuration,
            latestTimestamp: $latestTimestamp,
            localTimeOffsetSeconds: $localTimeOffsetSeconds,
        );
    }

    /**
     * @param array<array-key, mixed> $lastSequentialDetailFinishedAt
     *
     * @phpstan-assert array<string, Instant> $lastSequentialDetailFinishedAt
     */
    private static function assertState(
        Instant $startedAt,
        ?Instant $finishedAt,
        ?Instant $lastObservationAt,
        ?Instant $lastLapFinishedAt,
        ?Instant $lastSessionFinishedAt,
        ?Instant $lastSessionTimelineFinishedAt,
        ?Instant $lastDetailFinishedAt,
        array $lastSequentialDetailFinishedAt,
        int $sessionCount,
        Duration $recordedSessionTimerDuration,
        ?Instant $summaryReportedAt,
        ?string $type,
        ?Instant $pausedAt,
        Duration $accumulatedPausedDuration,
        Instant $latestTimestamp,
        ?Instant $timerStartedAt,
    ): void {
        if (0 > $sessionCount) {
            throw new InvalidActivitySnapshot('Activity snapshot session count cannot be negative.');
        }

        if (
            null !== $type
            && 1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $type,
            )
        ) {
            throw new InvalidActivitySnapshot('Activity snapshot type must use canonical snake_case notation.');
        }

        if ($latestTimestamp->isBefore($startedAt)) {
            throw new InvalidActivitySnapshot('Activity snapshot latest timestamp cannot precede its start.');
        }

        self::assertTimelineInstant(
            name: 'timer start',
            value: $timerStartedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'finish',
            value: $finishedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'latest observation',
            value: $lastObservationAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'latest lap finish',
            value: $lastLapFinishedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'latest session finish',
            value: $lastSessionFinishedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'latest detail finish',
            value: $lastDetailFinishedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );
        self::assertTimelineInstant(
            name: 'pause',
            value: $pausedAt,
            startedAt: $startedAt,
            latestTimestamp: $latestTimestamp,
        );

        $validatedSequenceFinishes = [];

        foreach (
            $lastSequentialDetailFinishedAt as $sequenceName => $finishedAtInSequence
        ) {
            if (
                !is_string($sequenceName)
                || 1 !== preg_match(
                    '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                    $sequenceName,
                )
            ) {
                throw new InvalidActivitySnapshot('Activity snapshot detail sequence name must use canonical snake_case notation.');
            }

            if (!$finishedAtInSequence instanceof Instant) {
                throw new InvalidActivitySnapshot('Activity snapshot detail sequence timestamps must be Instant values.');
            }

            self::assertTimelineInstant(
                name: sprintf(
                    'latest %s detail finish',
                    $sequenceName,
                ),
                value: $finishedAtInSequence,
                startedAt: $startedAt,
                latestTimestamp: $latestTimestamp,
            );
            $validatedSequenceFinishes[] = $finishedAtInSequence;
        }

        if ([] !== $lastSequentialDetailFinishedAt) {
            if (null === $lastDetailFinishedAt) {
                throw new InvalidActivitySnapshot('Activity snapshot detail sequences require a latest detail finish.');
            }

            foreach ($validatedSequenceFinishes as $finishedAtInSequence) {
                if ($finishedAtInSequence->isAfter($lastDetailFinishedAt)) {
                    throw new InvalidActivitySnapshot('Activity snapshot detail sequence cannot finish after the latest detail.');
                }
            }
        }

        if (
            $accumulatedPausedDuration->isLongerThan(
                Duration::between(
                    null === $summaryReportedAt && null !== $timerStartedAt
                        ? $timerStartedAt
                        : $startedAt,
                    $latestTimestamp,
                ),
            )
        ) {
            throw new InvalidActivitySnapshot('Activity snapshot paused duration cannot exceed its timeline duration.');
        }

        if (null !== $finishedAt) {
            if (null !== $pausedAt) {
                throw new InvalidActivitySnapshot('A finished activity snapshot cannot remain paused.');
            }

            if (!$finishedAt->equals($latestTimestamp)) {
                throw new InvalidActivitySnapshot('A finished activity snapshot must finish at its latest timestamp.');
            }
        }

        if (0 === $sessionCount) {
            if (null !== $lastSessionFinishedAt) {
                throw new InvalidActivitySnapshot('An activity snapshot without sessions cannot have a latest session finish.');
            }

            if (null !== $lastSessionTimelineFinishedAt) {
                throw new InvalidActivitySnapshot('An activity snapshot without sessions cannot have a session timeline finish.');
            }

            if (0 !== $recordedSessionTimerDuration->toMicroseconds()) {
                throw new InvalidActivitySnapshot('An activity snapshot without sessions must have zero recorded session duration.');
            }

            if (null !== $summaryReportedAt) {
                throw new InvalidActivitySnapshot('An activity snapshot without sessions cannot have a summary.');
            }
        } elseif (null === $lastSessionFinishedAt) {
            throw new InvalidActivitySnapshot('An activity snapshot with sessions requires a latest session finish.');
        } elseif (null === $lastSessionTimelineFinishedAt) {
            throw new InvalidActivitySnapshot('An activity snapshot with sessions requires a session timeline finish.');
        } elseif (
            $lastSessionTimelineFinishedAt->isBefore(
                $lastSessionFinishedAt,
            )
        ) {
            throw new InvalidActivitySnapshot('Activity snapshot session timeline cannot finish before its latest raw session boundary.');
        } elseif (
            $recordedSessionTimerDuration->isLongerThan(
                Duration::between(
                    $startedAt,
                    $lastSessionTimelineFinishedAt,
                ),
            )
        ) {
            throw new InvalidActivitySnapshot('Activity snapshot recorded session duration cannot exceed its normalized session timeline.');
        }

        if (
            null !== $summaryReportedAt
            && null !== $finishedAt
            && null !== $lastSessionTimelineFinishedAt
            && $lastSessionTimelineFinishedAt->isAfter($finishedAt)
        ) {
            throw new InvalidActivitySnapshot('A summarized activity snapshot must contain its normalized session timeline.');
        }

        if (null === $summaryReportedAt && null !== $type) {
            throw new InvalidActivitySnapshot('Activity snapshot type requires an applied summary.');
        }

        if (null !== $summaryReportedAt) {
            if ($summaryReportedAt->isBefore($startedAt)) {
                throw new InvalidActivitySnapshot('Activity snapshot summary cannot be reported before the activity starts.');
            }

            if (null === $finishedAt) {
                throw new InvalidActivitySnapshot('An activity snapshot with a summary must be finished.');
            }
        }
    }

    private static function assertTimelineInstant(
        string $name,
        ?Instant $value,
        Instant $startedAt,
        Instant $latestTimestamp,
    ): void {
        if (null === $value) {
            return;
        }

        if ($value->isBefore($startedAt)) {
            throw new InvalidActivitySnapshot(sprintf('Activity snapshot %s cannot precede its start.', $name));
        }

        if ($value->isAfter($latestTimestamp)) {
            throw new InvalidActivitySnapshot(sprintf('Activity snapshot %s cannot follow its latest timestamp.', $name));
        }
    }
}
