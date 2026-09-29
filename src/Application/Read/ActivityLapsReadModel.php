<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;

final readonly class ActivityLapsReadModel
{
    /**
     * @var list<ActivityLapReadModel>
     */
    private array $laps;

    /**
     * @param array<array-key, ActivityLapReadModel> $laps
     */
    public function __construct(
        public ActivityId $activityId,
        array $laps,
    ) {
        $previousLap = null;

        foreach ($laps as $index => $lap) {
            if ($lap->index !== $index) {
                throw new \InvalidArgumentException('Activity laps must use continuous zero-based indexes.');
            }

            if (
                null !== $previousLap
                && !$lap->adjacencyPolicy->allows(
                    previousEnd: $previousLap->finishedAt,
                    nextStart: $lap->startedAt,
                )
            ) {
                throw new \InvalidArgumentException(SummaryAdjacencyPolicy::AbutWithinTwoWholeSeconds === $lap->adjacencyPolicy ? 'Activity laps must be sequential and abut within two whole seconds.' : 'Activity laps cannot overlap under the selected adjacency policy.');
            }

            $previousLap = $lap;
        }

        $this->laps = array_values($laps);
    }

    public function count(): int
    {
        return count($this->laps);
    }

    /**
     * @return list<ActivityLapReadModel>
     */
    public function laps(): array
    {
        return $this->laps;
    }
}
