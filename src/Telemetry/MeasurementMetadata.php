<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

/**
 * Explicit, source-owned metadata representation, independent of PHP class names.
 */
interface MeasurementMetadata
{
    /** @return non-empty-string Stable, namespaced identifier. */
    public function metadataType(): string;

    /** @return positive-int Version of the data contract, not the implementation. */
    public function metadataVersion(): int;

    /**
     * Values must be null, booleans, integers, finite floats, strings, or arrays
     * recursively containing those values. Objects and resources are forbidden.
     * Keys and enum values are explicit contract names, not reflected properties.
     *
     * @return array<string, mixed>
     */
    public function metadataData(): array;
}
