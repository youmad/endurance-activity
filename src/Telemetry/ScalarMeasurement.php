<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class ScalarMeasurement implements Measurement
{
    public function __construct(
        private MeasurementType $measurementType,
        public int|float $value,
        public MeasurementUnit $unit,
    ) {
        if (
            is_float($value)
            && !is_finite($value)
        ) {
            throw new InvalidMeasurement('Scalar measurement value must be finite.');
        }
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }
}
