<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Summary;

use Youmad\Endurance\Activity\Exception\InvalidActivitySummary;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivitySummary
{
    private const int MINIMUM_SESSION_COUNT = 1;
    private const int MAXIMUM_SESSION_COUNT = 65_535;

    private function __construct(
        public Instant $reportedAt,
        public Duration $timerDuration,
        public int $sessionCount,
        public ?string $type,
        public ?int $localTimeOffsetSeconds,
    ) {
        if (
            self::MINIMUM_SESSION_COUNT > $sessionCount
            || self::MAXIMUM_SESSION_COUNT < $sessionCount
        ) {
            throw new InvalidActivitySummary('Activity summary session count must be between 1 and 65535.');
        }

        if (
            null !== $type
            && 1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $type,
            )
        ) {
            throw new InvalidActivitySummary('Activity summary type must use canonical snake_case notation.');
        }
    }

    public static function create(
        Instant $reportedAt,
        Duration $timerDuration,
        int $sessionCount,
        ?string $type = null,
        ?int $localTimeOffsetSeconds = null,
    ): self {
        return new self(
            reportedAt: $reportedAt,
            timerDuration: $timerDuration,
            sessionCount: $sessionCount,
            type: $type,
            localTimeOffsetSeconds: $localTimeOffsetSeconds,
        );
    }
}
