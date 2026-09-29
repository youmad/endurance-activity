<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

final readonly class ActivityDistance
{
    public function __construct(
        public float $value,
        public string $unit,
    ) {
        if (!is_finite($value) || 0.0 > $value) {
            throw new \InvalidArgumentException('Activity distance must be a finite non-negative value.');
        }

        if ('' === $unit || trim($unit) !== $unit) {
            throw new \InvalidArgumentException('Activity distance unit must be a non-empty canonical symbol.');
        }
    }
}
