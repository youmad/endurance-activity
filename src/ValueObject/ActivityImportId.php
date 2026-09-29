<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\ValueObject;

use Youmad\Endurance\Foundation\ValueObject\Uuid;

final readonly class ActivityImportId
{
    private function __construct(
        private Uuid $value,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::generate());
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function toString(): string
    {
        return $this->value->toString();
    }
}
