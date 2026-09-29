<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Activity\ValueObject\SummaryAdjacency;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class ActivitySessionReadModel
{
    private Duration $elapsedDuration;

    public SummaryAdjacencyPolicy $adjacencyPolicy;

    /**
     * @var list<ActivityScalarMeasurement>
     */
    private array $measurements;

    /**
     * @param array<array-key, mixed> $measurements
     */
    public function __construct(
        public int $index,
        public Instant $startedAt,
        public Instant $finishedAt,
        public Duration $timerDuration,
        public ?string $sport,
        public ?string $subSport,
        public ?Coordinate $startPosition,
        public ?Coordinate $endPosition,
        public ?ActivityDistance $distance,
        public TemporalResolution $timelineResolution =
            TemporalResolution::Microsecond,
        array $measurements = [],
        ?SummaryAdjacencyPolicy $adjacencyPolicy = null,
    ) {
        $this->adjacencyPolicy = $adjacencyPolicy ?? SummaryAdjacency::legacyPolicy($timelineResolution);

        if (0 > $index) {
            throw new \InvalidArgumentException('Activity session index cannot be negative.');
        }

        $this->elapsedDuration = Duration::between(
            $startedAt,
            $finishedAt,
        );

        if ($timerDuration->isLongerThan($this->elapsedDuration)) {
            throw new \InvalidArgumentException('Activity session timer duration cannot exceed elapsed duration.');
        }

        self::assertOptionalIdentifier('sport', $sport);
        self::assertOptionalIdentifier('sub-sport', $subSport);

        if (null === $sport && null !== $subSport) {
            throw new \InvalidArgumentException('Activity session sub-sport requires a sport.');
        }

        $this->measurements = $this->validateMeasurements($measurements);
    }

    public function elapsedDuration(): Duration
    {
        return $this->elapsedDuration;
    }

    /**
     * @return list<ActivityScalarMeasurement>
     */
    public function measurements(): array
    {
        return $this->measurements;
    }

    /**
     * @param array<array-key, mixed> $measurements
     *
     * @return list<ActivityScalarMeasurement>
     */
    private function validateMeasurements(array $measurements): array
    {
        $knownTypes = [];
        $validated = [];

        foreach ($measurements as $measurement) {
            if (!$measurement instanceof ActivityScalarMeasurement) {
                throw new \InvalidArgumentException('Activity session scalar measurements must be ActivityScalarMeasurement instances.');
            }

            if (isset($knownTypes[$measurement->type])) {
                throw new \InvalidArgumentException(sprintf('Activity session contains scalar measurement type %s more than once.', $measurement->type));
            }

            $knownTypes[$measurement->type] = true;
            $validated[] = $measurement;
        }

        return $validated;
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
            throw new \InvalidArgumentException(sprintf('Activity session %s must use canonical snake_case notation.', $field));
        }
    }
}
