<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Activity\Exception\InvalidActivityImportIdempotencyKey;

final readonly class ActivityImportIdempotencyKey
{
    public const int MAX_LENGTH = 255;

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (
            '' === $value
            || trim($value) !== $value
            || self::MAX_LENGTH < strlen($value)
        ) {
            throw InvalidActivityImportIdempotencyKey::forValue(value: $value, maximumLength: self::MAX_LENGTH);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
