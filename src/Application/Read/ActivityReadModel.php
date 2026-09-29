<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityReadModel
{
    private ?Duration $elapsedDuration;

    /**
     * @var list<ActivitySessionReadModel>
     */
    private array $sessions;

    /**
     * @param array<array-key, ActivitySessionReadModel> $sessions
     */
    public function __construct(
        public ActivityId $id,
        public Instant $startedAt,
        public ?Instant $finishedAt,
        public ?string $type,
        public Duration $timerDuration,
        public Duration $pausedDuration,
        public ?ActivityDistance $distance,
        array $sessions,
        public ?int $localTimeOffsetSeconds = null,
    ) {
        if (
            null !== $type
            && 1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $type,
            )
        ) {
            throw new \InvalidArgumentException('Activity type must use canonical snake_case notation.');
        }

        $this->elapsedDuration = null === $finishedAt
            ? null
            : Duration::between($startedAt, $finishedAt);

        if (
            null !== $this->elapsedDuration
            && $pausedDuration->isLongerThan($this->elapsedDuration)
        ) {
            throw new \InvalidArgumentException('Activity paused duration cannot exceed elapsed duration.');
        }

        $recordedTimerDuration = Duration::zero();
        $previousSession = null;

        foreach ($sessions as $index => $session) {
            if ($session->index !== $index) {
                throw new \InvalidArgumentException('Activity sessions must use continuous zero-based indexes.');
            }

            if ($session->startedAt->isBefore($startedAt)) {
                throw new \InvalidArgumentException('Activity session cannot start before the activity.');
            }

            if (
                null !== $finishedAt
                && $session->finishedAt->isAfter($finishedAt)
            ) {
                throw new \InvalidArgumentException('Activity session cannot finish after the activity.');
            }

            if (
                null !== $previousSession
                && !$session->adjacencyPolicy->allows(
                    previousEnd: $previousSession->finishedAt,
                    nextStart: $session->startedAt,
                )
            ) {
                throw new \InvalidArgumentException(SummaryAdjacencyPolicy::AbutWithinTwoWholeSeconds === $session->adjacencyPolicy ? 'Activity sessions must be sequential and abut within two whole seconds.' : 'Activity sessions cannot overlap under the selected adjacency policy.');
            }

            $recordedTimerDuration = $recordedTimerDuration->plus(
                $session->timerDuration,
            );
            $previousSession = $session;
        }

        if (!$timerDuration->equals($recordedTimerDuration)) {
            throw new \InvalidArgumentException('Activity timer duration must equal the sum of session timer durations.');
        }

        if (
            null !== $this->elapsedDuration
            && $timerDuration->isLongerThan($this->elapsedDuration)
        ) {
            throw new \InvalidArgumentException('Activity timer duration cannot exceed elapsed duration.');
        }

        $this->sessions = array_values($sessions);
    }

    public function elapsedDuration(): ?Duration
    {
        return $this->elapsedDuration;
    }

    public function sessionCount(): int
    {
        return count($this->sessions);
    }

    /**
     * @return list<ActivitySessionReadModel>
     */
    public function sessions(): array
    {
        return $this->sessions;
    }
}
