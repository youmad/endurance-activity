<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

/** Projection for consumers that can represent only one reading per type. */
final class UnambiguousScalarMeasurements
{
    /**
     * Equal values or matching sources do not establish a preferred reading.
     *
     * @param list<ActivityScalarMeasurement> $measurements
     *
     * @return array<string, ActivityScalarMeasurement>
     */
    public static function byType(array $measurements): array
    {
        $seen = [];
        $unique = [];
        foreach ($measurements as $measurement) {
            $type = $measurement->type;
            if (isset($seen[$type])) {
                unset($unique[$type]);
                continue;
            }

            $seen[$type] = true;
            $unique[$type] = $measurement;
        }

        return $unique;
    }
}
