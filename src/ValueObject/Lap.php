<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Activity\Exception\InvalidLap;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class Lap
{
    private Duration $elapsedDuration;

    /**
     * @var list<MeasurementReading>
     */
    private array $readings;

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    private function __construct(
        public Instant $startedAt,
        public Instant $finishedAt,
        public Duration $timerDuration,
        public TemporalResolution $timelineResolution,
        public SummaryAdjacencyPolicy $adjacencyPolicy,
        array $readings,
    ) {
        if ($finishedAt->isBefore($startedAt)) {
            throw new InvalidLap('Lap cannot finish before it starts.');
        }

        $this->elapsedDuration = Duration::between(
            $startedAt,
            $finishedAt,
        );

        // Lap timer and elapsed duration are independent source values.
        // Preserve both values without requiring timer <= elapsed or changing
        // the recorded time boundaries.

        $knownMeasurementTypes = [];

        foreach ($readings as $reading) {
            $measurementType = $reading
                ->measurement
                ->type()
                ->toString();

            if (isset($knownMeasurementTypes[$measurementType])) {
                throw new InvalidLap(sprintf('Lap contains measurement type %s more than once.', $measurementType));
            }

            $knownMeasurementTypes[$measurementType] = true;
        }

        $this->readings = array_values($readings);
    }

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    public static function create(
        Instant $startedAt,
        Instant $finishedAt,
        Duration $timerDuration,
        array $readings = [],
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
        ?SummaryAdjacencyPolicy $adjacencyPolicy = null,
    ): self {
        return new self(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: $timerDuration,
            timelineResolution: $timelineResolution,
            adjacencyPolicy: $adjacencyPolicy ?? SummaryAdjacency::legacyPolicy($timelineResolution),
            readings: $readings,
        );
    }

    public function elapsedDuration(): Duration
    {
        return $this->elapsedDuration;
    }

    /**
     * @return list<MeasurementReading>
     */
    public function readings(): array
    {
        return $this->readings;
    }
}
