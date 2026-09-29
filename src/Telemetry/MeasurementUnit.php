<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

use Youmad\Endurance\Activity\Exception\InvalidMeasurement;

final readonly class MeasurementUnit
{
    private function __construct(
        private string $symbol,
    ) {
    }

    public static function fromSymbol(string $symbol): self
    {
        if (
            '' === $symbol
            || trim($symbol) !== $symbol
        ) {
            throw new InvalidMeasurement('Measurement unit must have a non-empty canonical symbol.');
        }

        return new self($symbol);
    }

    public static function none(): self
    {
        return new self('1');
    }

    public function equals(self $other): bool
    {
        return $this->symbol === $other->symbol;
    }

    public function toString(): string
    {
        return $this->symbol;
    }
}
