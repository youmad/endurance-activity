<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class Vector3Measurement implements Measurement
{
    public function __construct(
        private MeasurementType $measurementType,
        public float $x,
        public float $y,
        public float $z,
        public MeasurementUnit $unit,
    ) {
        if (
            !is_finite($x)
            || !is_finite($y)
            || !is_finite($z)
        ) {
            throw new InvalidMeasurement('Vector measurement components must be finite.');
        }
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }
}
