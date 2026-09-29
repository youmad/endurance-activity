<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class ArrayMeasurement implements Measurement
{
    /**
     * @var non-empty-list<int|float|string|null>
     */
    private array $values;

    /**
     * @param array<array-key, int|float|string|null> $values
     */
    public function __construct(
        private MeasurementType $measurementType,
        array $values,
        public MeasurementUnit $unit,
    ) {
        if ([] === $values) {
            throw new InvalidMeasurement('Array measurement must contain at least one element.');
        }

        $hasValue = false;

        foreach ($values as $value) {
            if (null === $value) {
                continue;
            }

            $hasValue = true;

            if (
                is_float($value)
                && !is_finite($value)
            ) {
                throw new InvalidMeasurement('Array measurement numeric elements must be finite.');
            }

            if (
                is_string($value)
                && (
                    '' === $value
                    || trim($value) !== $value
                )
            ) {
                throw new InvalidMeasurement('Array measurement text elements must be non-empty trimmed strings.');
            }
        }

        if (!$hasValue) {
            throw new InvalidMeasurement('Array measurement must contain at least one valid element.');
        }

        $this->values = array_values($values);
    }

    public function type(): MeasurementType
    {
        return $this->measurementType;
    }

    /**
     * @return non-empty-list<int|float|string|null>
     */
    public function values(): array
    {
        return $this->values;
    }
}
