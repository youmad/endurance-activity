<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Foundation\ValueObject\Coordinate;

final readonly class PositionMeasurement implements Measurement
{
    private MeasurementType $measurementType;

    public function __construct(
        public Coordinate $coordinate,
    ) {
        $this->measurementType = MeasurementType::fromString(
            'position',
        );
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }
}
