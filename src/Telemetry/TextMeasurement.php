<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class TextMeasurement implements Measurement
{
    public function __construct(
        private MeasurementType $measurementType,
        public string $value,
    ) {
        if (
            '' === $value
            || trim($value) !== $value
        ) {
            throw new InvalidMeasurement('Text measurement value must be a non-empty trimmed string.');
        }
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }
}
