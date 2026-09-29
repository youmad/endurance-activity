<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

/**
 * Compatibility adapter for summaries that encode their sequence rule through
 * timeline resolution. New summaries persist an explicit policy; this mapping
 * remains for legacy callers and stored payloads. Resolution alone is not a
 * general sequence rule.
 */
final class SummaryAdjacency
{
    public static function allows(
        Instant $previousEnd,
        Instant $nextStart,
        TemporalResolution $resolution,
    ): bool {
        return self::legacyPolicy($resolution)->allows($previousEnd, $nextStart);
    }

    /** Compatibility for callers and payloads predating explicit policy. */
    public static function legacyPolicy(TemporalResolution $resolution): SummaryAdjacencyPolicy
    {
        return match ($resolution) {
            TemporalResolution::Second => SummaryAdjacencyPolicy::AbutWithinTwoWholeSeconds,
            TemporalResolution::Microsecond => SummaryAdjacencyPolicy::NonOverlapping,
        };
    }
}
