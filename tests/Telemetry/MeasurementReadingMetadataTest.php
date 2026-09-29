<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Telemetry;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Telemetry\MeasurementMetadata;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;

final class MeasurementReadingMetadataTest extends TestCase
{
    public function testCanPreserveSourceSpecificMetadata(): void
    {
        $metadata = new TestMeasurementMetadata(
            identifier: 'external-field',
        );

        $reading = MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'external_value',
                ),
                value: 42,
                unit: MeasurementUnit::none(),
            ),
            source: MeasurementSource::unknown(),
            metadata: $metadata,
        );

        self::assertSame(
            $metadata,
            $reading->metadata,
        );
    }

    public function testMetadataRemainsOptional(): void
    {
        $reading = MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'external_value',
                ),
                value: 42,
                unit: MeasurementUnit::none(),
            ),
            source: MeasurementSource::unknown(),
        );

        self::assertNull($reading->metadata);
    }
}

final readonly class TestMeasurementMetadata implements MeasurementMetadata
{
    public function __construct(
        public string $identifier,
    ) {
    }

    public function metadataType(): string
    {
        return 'test.external_field';
    }

    public function metadataVersion(): int
    {
        return 1;
    }

    public function metadataData(): array
    {
        return ['identifier' => $this->identifier];
    }
}
