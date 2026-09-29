<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityTrackCursor
{
    public function __construct(
        public Instant $observedAt,
        public int $observationId,
    ) {
        if (1 > $observationId) {
            throw new \InvalidArgumentException('Activity track observation ID must be positive.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->observedAt->equals($other->observedAt)
            && $this->observationId === $other->observationId;
    }

    public function isBefore(self $other): bool
    {
        if ($this->observedAt->isBefore($other->observedAt)) {
            return true;
        }

        if ($this->observedAt->isAfter($other->observedAt)) {
            return false;
        }

        return $this->observationId < $other->observationId;
    }
}
