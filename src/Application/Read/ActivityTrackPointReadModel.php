<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Read;

use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityTrackPointReadModel
{
    /**
     * @var list<ActivityScalarMeasurement>
     */
    private array $measurements;

    /**
     * @param array<array-key, mixed> $measurements
     */
    public function __construct(
        public ActivityTrackCursor $cursor,
        public Instant $timestamp,
        public ?Coordinate $position,
        public ?ActivityScalarMeasurement $altitude,
        public ?ActivityScalarMeasurement $distance,
        public ?ActivityScalarMeasurement $speed,
        public ?ActivityScalarMeasurement $heartRate,
        public ?ActivityScalarMeasurement $cadence,
        public ?ActivityScalarMeasurement $power,
        public ?ActivityScalarMeasurement $temperature,
        array $measurements = [],
    ) {
        if (!$cursor->observedAt->equals($timestamp)) {
            throw new \InvalidArgumentException('Activity track point timestamp must match its cursor timestamp.');
        }

        $this->assertConvenienceType($altitude, 'altitude');
        $this->assertConvenienceType($distance, 'distance');
        $this->assertConvenienceType($speed, 'speed');
        $this->assertConvenienceType($heartRate, 'heart_rate');
        $this->assertConvenienceType($cadence, 'cadence');
        $this->assertConvenienceType($power, 'power');
        $this->assertConvenienceType($temperature, 'temperature');

        $validated = [];

        foreach ($measurements as $measurement) {
            if (!$measurement instanceof ActivityScalarMeasurement) {
                throw new \InvalidArgumentException('Activity track scalar measurements must be ActivityScalarMeasurement instances.');
            }

            $validated[] = $measurement;
        }

        $this->measurements = $validated;
    }

    /**
     * @return list<ActivityScalarMeasurement>
     */
    public function measurements(): array
    {
        return $this->measurements;
    }

    private function assertConvenienceType(
        ?ActivityScalarMeasurement $measurement,
        string $expectedType,
    ): void {
        if (null === $measurement) {
            return;
        }

        if ($expectedType !== $measurement->type) {
            throw new \InvalidArgumentException(sprintf('Activity track %s measurement must have type %s.', $expectedType, $expectedType));
        }
    }
}
