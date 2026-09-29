<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidActivityObservation;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityObservation
{
    /**
     * @var non-empty-list<MeasurementReading>
     */
    private array $readings;

    /**
     * @param non-empty-list<MeasurementReading> $readings
     */
    private function __construct(
        public Instant $timestamp,
        array $readings,
    ) {
        $this->readings = $readings;
    }

    public static function create(
        Instant $timestamp,
        Measurement ...$measurements,
    ): self {
        if ([] === $measurements) {
            throw new InvalidActivityObservation('Activity observation must contain at least one measurement.');
        }

        $readings = array_map(
            static fn (
                Measurement $measurement,
            ): MeasurementReading => MeasurementReading::reported(
                measurement: $measurement,
                source: MeasurementSource::unknown(),
            ),
            $measurements,
        );

        return new self(
            timestamp: $timestamp,
            readings: $readings,
        );
    }

    public static function fromReadings(
        Instant $timestamp,
        MeasurementReading ...$readings,
    ): self {
        if ([] === $readings) {
            throw new InvalidActivityObservation('Activity observation must contain at least one reading.');
        }

        return new self(
            timestamp: $timestamp,
            readings: $readings,
        );
    }

    /**
     * @return non-empty-list<MeasurementReading>
     */
    public function readings(): array
    {
        return $this->readings;
    }
}
