<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail;

use Youmad\Endurance\Activity\Exception\InvalidActivityInterval;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityInterval
{
    private Duration $elapsedDuration;

    private function __construct(
        public Instant $startedAt,
        public Instant $finishedAt,
        public Duration $timerDuration,
    ) {
        if ($finishedAt->isBefore($startedAt)) {
            throw new InvalidActivityInterval('Activity interval cannot finish before it starts.');
        }

        $this->elapsedDuration = Duration::between(
            $startedAt,
            $finishedAt,
        );

        if ($timerDuration->isLongerThan($this->elapsedDuration)) {
            throw new InvalidActivityInterval('Activity interval timer duration cannot exceed its elapsed duration.');
        }
    }

    public static function create(
        Instant $startedAt,
        Instant $finishedAt,
        Duration $timerDuration,
    ): self {
        return new self(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: $timerDuration,
        );
    }

    public function elapsedDuration(): Duration
    {
        return $this->elapsedDuration;
    }
}
