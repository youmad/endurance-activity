<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class LapAdjacency
{
    public static function allows(
        Instant $previousEnd,
        Instant $nextStart,
        TemporalResolution $resolution,
    ): bool {
        return SummaryAdjacency::allows(
            previousEnd: $previousEnd,
            nextStart: $nextStart,
            resolution: $resolution,
        );
    }
}
