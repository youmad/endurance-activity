<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class TrackPoint
{
    public function __construct(
        public Instant $timestamp,
        public Coordinate $coordinate,
    ) {
    }
}
