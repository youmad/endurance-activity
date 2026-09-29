<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\SummaryAdjacency;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class ActivityLapReadModel
{
    private Duration $elapsedDuration;

    public SummaryAdjacencyPolicy $adjacencyPolicy;

    /**
     * @var list<ActivityScalarMeasurement>
     */
    private array $measurements;

    /**
     * @param array<array-key, mixed> $measurements
     */
    public function __construct(
        public int $index,
        public Instant $startedAt,
        public Instant $finishedAt,
        public Duration $timerDuration,
        public ?ActivityDistance $distance,
        public TemporalResolution $timelineResolution =
            TemporalResolution::Microsecond,
        array $measurements = [],
        ?SummaryAdjacencyPolicy $adjacencyPolicy = null,
    ) {
        $this->adjacencyPolicy = $adjacencyPolicy ?? SummaryAdjacency::legacyPolicy($timelineResolution);

        if (0 > $index) {
            throw new \InvalidArgumentException('Activity lap index cannot be negative.');
        }

        if ($finishedAt->isBefore($startedAt)) {
            throw new \InvalidArgumentException('Activity lap cannot finish before it starts.');
        }

        $this->elapsedDuration = Duration::between(
            $startedAt,
            $finishedAt,
        );

        $this->measurements = $this->validateMeasurements($measurements);
    }

    public function elapsedDuration(): Duration
    {
        return $this->elapsedDuration;
    }

    /**
     * @return list<ActivityScalarMeasurement>
     */
    public function measurements(): array
    {
        return $this->measurements;
    }

    /**
     * @param array<array-key, mixed> $measurements
     *
     * @return list<ActivityScalarMeasurement>
     */
    private function validateMeasurements(array $measurements): array
    {
        $knownTypes = [];
        $validated = [];

        foreach ($measurements as $measurement) {
            if (!$measurement instanceof ActivityScalarMeasurement) {
                throw new \InvalidArgumentException('Activity lap scalar measurements must be ActivityScalarMeasurement instances.');
            }

            if (isset($knownTypes[$measurement->type])) {
                throw new \InvalidArgumentException(sprintf('Activity lap contains scalar measurement type %s more than once.', $measurement->type));
            }

            $knownTypes[$measurement->type] = true;
            $validated[] = $measurement;
        }

        return $validated;
    }
}
