<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\Telemetry\MeasurementOrigin;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;

final readonly class ActivityScalarMeasurement
{
    public MeasurementSource $source;

    public function __construct(
        public string $type,
        public int|float $value,
        public string $unit,
        public ?MeasurementOrigin $origin = null,
        ?MeasurementSource $source = null,
    ) {
        $this->source = $source ?? MeasurementSource::unknown();

        if (
            1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $type,
            )
        ) {
            throw new \InvalidArgumentException('Activity scalar measurement type must use canonical snake_case notation.');
        }

        if (is_float($value) && !is_finite($value)) {
            throw new \InvalidArgumentException('Activity scalar measurement value must be finite.');
        }

        if ('' === $unit || trim($unit) !== $unit) {
            throw new \InvalidArgumentException('Activity scalar measurement unit must be a non-empty canonical symbol.');
        }
    }
}
