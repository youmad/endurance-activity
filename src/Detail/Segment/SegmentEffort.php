<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail\Segment;

use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Exception\InvalidSegmentEffort;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;

final readonly class SegmentEffort implements ActivityDetail
{
    /**
     * @var list<MeasurementReading>
     */
    private array $readings;

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    private function __construct(
        private ActivityInterval $interval,
        public ?string $segmentId,
        public ?string $name,
        public ?string $status,
        public ?string $sport,
        public ?string $subSport,
        public ?string $manufacturer,
        public ?Coordinate $startPosition,
        public ?Coordinate $endPosition,
        array $readings,
    ) {
        self::assertOptionalText(
            field: 'identifier',
            value: $segmentId,
        );

        self::assertOptionalText(
            field: 'name',
            value: $name,
        );

        self::assertOptionalIdentifier(
            field: 'status',
            value: $status,
        );

        self::assertOptionalIdentifier(
            field: 'sport',
            value: $sport,
        );

        self::assertOptionalIdentifier(
            field: 'sub-sport',
            value: $subSport,
        );

        self::assertOptionalIdentifier(
            field: 'manufacturer',
            value: $manufacturer,
        );

        if (
            null === $sport
            && null !== $subSport
        ) {
            throw new InvalidSegmentEffort('Segment effort sub-sport requires a sport.');
        }

        $knownMeasurementTypes = [];

        foreach ($readings as $reading) {
            $measurementType = $reading
                ->measurement
                ->type()
                ->toString();

            if (isset($knownMeasurementTypes[$measurementType])) {
                throw new InvalidSegmentEffort(sprintf('Segment effort contains measurement type %s more than once.', $measurementType));
            }

            $knownMeasurementTypes[$measurementType] = true;
        }

        $this->readings = array_values($readings);
    }

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    public static function create(
        ActivityInterval $interval,
        ?string $segmentId = null,
        ?string $name = null,
        ?string $status = null,
        ?string $sport = null,
        ?string $subSport = null,
        ?string $manufacturer = null,
        ?Coordinate $startPosition = null,
        ?Coordinate $endPosition = null,
        array $readings = [],
    ): self {
        return new self(
            interval: $interval,
            segmentId: $segmentId,
            name: $name,
            status: $status,
            sport: $sport,
            subSport: $subSport,
            manufacturer: $manufacturer,
            startPosition: $startPosition,
            endPosition: $endPosition,
            readings: $readings,
        );
    }

    public function interval(): ActivityInterval
    {
        return $this->interval;
    }

    /**
     * @return list<MeasurementReading>
     */
    public function readings(): array
    {
        return $this->readings;
    }

    private static function assertOptionalText(
        string $field,
        ?string $value,
    ): void {
        if (null === $value) {
            return;
        }

        if (
            '' === $value
            || trim($value) !== $value
        ) {
            throw new InvalidSegmentEffort(sprintf('Segment effort %s must be a non-empty trimmed string.', $field));
        }
    }

    private static function assertOptionalIdentifier(
        string $field,
        ?string $value,
    ): void {
        if (null === $value) {
            return;
        }

        if (
            1 !== preg_match(
                '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                $value,
            )
        ) {
            throw new InvalidSegmentEffort(sprintf('Segment effort %s must use canonical snake_case notation.', $field));
        }
    }
}
