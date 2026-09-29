<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Foundation\ValueObject\Instant;

/**
 * A sequence rule, independent of the precision of its timestamps.
 */
enum SummaryAdjacencyPolicy: string
{
    case NonOverlapping = 'non_overlapping';
    case AbutWithinTwoWholeSeconds = 'abut_within_two_whole_seconds';

    public function allows(Instant $previousEnd, Instant $nextStart): bool
    {
        if (self::NonOverlapping === $this) {
            return !$nextStart->isBefore($previousEnd);
        }

        // Compare the whole-second views of both boundaries, not a rounded
        // duration between them. Keep the original instants unchanged.
        $previousEndSeconds = (int) $previousEnd
            ->toDateTimeImmutable()->format('U');
        $nextStartSeconds = (int) $nextStart
            ->toDateTimeImmutable()->format('U');

        return abs($nextStartSeconds - $previousEndSeconds) <= 2;
    }
}
