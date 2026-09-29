<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Session;

use Youmad\Endurance\Activity\Exception\InvalidActivitySession;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacency;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class ActivitySession
{
    private Duration $elapsedDuration;

    /**
     * @var list<MeasurementReading>
     */
    private array $readings;

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    private function __construct(
        public Instant $startedAt,
        public Instant $finishedAt,
        public Duration $timerDuration,
        public ?string $sport,
        public ?string $subSport,
        public ?Coordinate $startPosition,
        public ?Coordinate $endPosition,
        public TemporalResolution $timelineResolution,
        public SummaryAdjacencyPolicy $adjacencyPolicy,
        array $readings,
    ) {
        if ($finishedAt->isBefore($startedAt)) {
            throw new InvalidActivitySession('Activity session cannot finish before it starts.');
        }

        $this->elapsedDuration = Duration::between(
            $startedAt,
            $finishedAt,
        );

        if ($timerDuration->isLongerThan($this->elapsedDuration)) {
            throw new InvalidActivitySession('Activity session timer duration cannot exceed its elapsed duration.');
        }

        self::assertOptionalIdentifier(
            field: 'sport',
            value: $sport,
        );

        self::assertOptionalIdentifier(
            field: 'sub-sport',
            value: $subSport,
        );

        if (
            null === $sport
            && null !== $subSport
        ) {
            throw new InvalidActivitySession('Activity session sub-sport requires a sport.');
        }

        $knownMeasurementTypes = [];

        foreach ($readings as $reading) {
            $measurementType = $reading
                ->measurement
                ->type()
                ->toString();

            if (isset($knownMeasurementTypes[$measurementType])) {
                throw new InvalidActivitySession(sprintf('Activity session contains measurement type %s more than once.', $measurementType));
            }

            $knownMeasurementTypes[$measurementType] = true;
        }

        $this->readings = array_values($readings);
    }

    /**
     * @param array<array-key, MeasurementReading> $readings
     */
    public static function create(
        Instant $startedAt,
        Instant $finishedAt,
        Duration $timerDuration,
        ?string $sport = null,
        ?string $subSport = null,
        ?Coordinate $startPosition = null,
        ?Coordinate $endPosition = null,
        array $readings = [],
        TemporalResolution $timelineResolution = TemporalResolution::Microsecond,
        ?SummaryAdjacencyPolicy $adjacencyPolicy = null,
    ): self {
        return new self(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: $timerDuration,
            sport: $sport,
            subSport: $subSport,
            startPosition: $startPosition,
            endPosition: $endPosition,
            timelineResolution: $timelineResolution,
            adjacencyPolicy: $adjacencyPolicy ?? SummaryAdjacency::legacyPolicy($timelineResolution),
            readings: $readings,
        );
    }

    public function elapsedDuration(): Duration
    {
        return $this->elapsedDuration;
    }

    /**
     * @return list<MeasurementReading>
     */
    public function readings(): array
    {
        return $this->readings;
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
            throw new InvalidActivitySession(sprintf('Activity session %s must use canonical snake_case notation.', $field));
        }
    }
}
