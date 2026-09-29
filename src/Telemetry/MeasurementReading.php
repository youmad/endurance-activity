<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

final readonly class MeasurementReading
{
    private function __construct(
        public Measurement $measurement,
        public MeasurementOrigin $origin,
        public MeasurementSource $source,
        public ?MeasurementMetadata $metadata,
    ) {
    }

    public static function reported(
        Measurement $measurement,
        MeasurementSource $source,
        ?MeasurementMetadata $metadata = null,
    ): self {
        return new self(
            measurement: $measurement,
            origin: MeasurementOrigin::Reported,
            source: $source,
            metadata: $metadata,
        );
    }

    public static function derived(
        Measurement $measurement,
        MeasurementSource $source,
        ?MeasurementMetadata $metadata = null,
    ): self {
        return new self(
            measurement: $measurement,
            origin: MeasurementOrigin::Derived,
            source: $source,
            metadata: $metadata,
        );
    }
}
