<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail\Pool;

use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\SequentialActivityDetail;
use Youmad\Endurance\Activity\Exception\InvalidPoolLength;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class PoolLength implements SequentialActivityDetail
{
    public const string SEQUENCE_NAME = 'pool_length';

    /**
     * @var list<MeasurementReading>
     */
    private array $readings;

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    private function __construct(
        private ActivityInterval $interval,
        public PoolLengthType $type,
        public ?string $stroke,
        public TemporalResolution $timelineResolution,
        array $readings,
    ) {
        if (null !== $stroke) {
            if (
                1 !== preg_match(
                    '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                    $stroke,
                )
            ) {
                throw new InvalidPoolLength('Pool length stroke must use canonical snake_case notation.');
            }

            if (PoolLengthType::Idle === $type) {
                throw new InvalidPoolLength('Idle pool length cannot declare a swim stroke.');
            }
        }

        $knownMeasurementTypes = [];

        foreach ($readings as $reading) {
            $measurementType = $reading
                ->measurement
                ->type()
                ->toString();

            if (isset($knownMeasurementTypes[$measurementType])) {
                throw new InvalidPoolLength(sprintf('Pool length contains measurement type %s more than once.', $measurementType));
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
        PoolLengthType $type,
        ?string $stroke = null,
        array $readings = [],
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
    ): self {
        return new self(
            interval: $interval,
            type: $type,
            stroke: $stroke,
            timelineResolution: $timelineResolution,
            readings: $readings,
        );
    }

    public function interval(): ActivityInterval
    {
        return $this->interval;
    }

    public function sequenceName(): string
    {
        return self::SEQUENCE_NAME;
    }

    public function timelineResolution(): TemporalResolution
    {
        return $this->timelineResolution;
    }

    /**
     * @return list<MeasurementReading>
     */
    public function readings(): array
    {
        return $this->readings;
    }
}
