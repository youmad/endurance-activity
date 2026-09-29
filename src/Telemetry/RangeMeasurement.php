<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class RangeMeasurement implements Measurement
{
    public function __construct(
        private MeasurementType $measurementType,
        public float $minimum,
        public float $maximum,
        public MeasurementUnit $unit,
    ) {
        if (
            !is_finite($minimum)
            || !is_finite($maximum)
        ) {
            throw new InvalidMeasurement('Range measurement boundaries must be finite.');
        }

        if ($minimum > $maximum) {
            throw new InvalidMeasurement('Range minimum cannot exceed its maximum.');
        }
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }
}
