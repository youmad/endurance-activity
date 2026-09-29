<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

final class InvalidActivityImportIdempotencyKey extends \InvalidArgumentException
{
    public static function forValue(
        string $value,
        int $maximumLength,
    ): self {
        return new self(
            sprintf(
                'Activity import idempotency key must be non-empty, must not contain surrounding whitespace, and must not exceed %d bytes; got %d bytes.',
                $maximumLength,
                strlen($value),
            ),
        );
    }
}
