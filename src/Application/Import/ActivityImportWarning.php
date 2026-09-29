<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

final readonly class ActivityImportWarning
{
    private const int MAX_MESSAGE_BYTES = 512;

    /** @var array<string, bool|float|int|string|null> */
    public array $context;

    /**
     * @param array<array-key, mixed> $context
     */
    public function __construct(
        public ActivityImportWarningCode $code,
        public string $message,
        array $context = [],
    ) {
        if (
            '' === $message
            || self::MAX_MESSAGE_BYTES < strlen($message)
        ) {
            throw new \InvalidArgumentException('Activity import warning message must contain 1 to 512 bytes.');
        }

        $validated = [];

        foreach ($context as $name => $value) {
            if (
                !is_string($name)
                || 1 !== preg_match(
                    '/^[a-z][a-zA-Z0-9]*$/',
                    $name,
                )
            ) {
                throw new \InvalidArgumentException('Activity import warning context keys must use camelCase notation.');
            }

            if (
                null !== $value
                && !is_bool($value)
                && !is_float($value)
                && !is_int($value)
                && !is_string($value)
            ) {
                throw new \InvalidArgumentException('Activity import warning context values must be scalar or null.');
            }

            if (is_float($value) && !is_finite($value)) {
                throw new \InvalidArgumentException('Activity import warning context floats must be finite.');
            }

            $validated[$name] = $value;
        }

        $this->context = $validated;
    }
}
