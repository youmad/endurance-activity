<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class MeasurementType
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (
            1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $value,
            )
        ) {
            throw new InvalidMeasurement('Measurement type must use canonical snake_case notation.');
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
