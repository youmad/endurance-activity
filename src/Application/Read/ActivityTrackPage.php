<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class ActivityTrackPage
{
    /**
     * @var list<ActivityTrackPointReadModel>
     */
    private array $points;

    /**
     * @param array<array-key, ActivityTrackPointReadModel> $points
     */
    public function __construct(
        public ActivityId $activityId,
        array $points,
        public ?ActivityTrackCursor $nextCursor,
    ) {
        $previous = null;

        foreach ($points as $point) {
            if (
                null !== $previous
                && !$previous->cursor->isBefore($point->cursor)
            ) {
                throw new \InvalidArgumentException('Activity track points must use a strictly increasing cursor order.');
            }

            $previous = $point;
        }

        if ([] === $points && null !== $nextCursor) {
            throw new \InvalidArgumentException('An empty activity track page cannot have a next cursor.');
        }

        if (
            null !== $nextCursor
            && !$nextCursor->equals($points[array_key_last($points)]->cursor)
        ) {
            throw new \InvalidArgumentException('Activity track next cursor must reference the final returned point.');
        }

        $this->points = array_values($points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    /**
     * @return list<ActivityTrackPointReadModel>
     */
    public function points(): array
    {
        return $this->points;
    }
}
