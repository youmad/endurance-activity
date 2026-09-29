<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Detail\Segment;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\Segment\SegmentEffort;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\InvalidSegmentEffort;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class SegmentEffortTest extends TestCase
{
    public function testCreatesOverlappingRouteSegmentDetailsWithMetadataAndReadings(): void
    {
        $effort = SegmentEffort::create(
            interval: $this->interval(),
            segmentId: 'f784f6d0-1337-4a8a-92c8-001122334455',
            name: 'Harju climb',
            status: 'end',
            sport: 'cycling',
            subSport: 'road',
            manufacturer: 'garmin',
            startPosition: new Coordinate(59.43, 24.75),
            endPosition: new Coordinate(59.44, 24.76),
            readings: [
                $this->reading(
                    type: 'total_distance',
                    value: 1_234.5,
                    unit: 'm',
                ),
            ],
        );

        $overlapping = SegmentEffort::create(interval: $this->interval());
        $activity = Activity::start($effort->interval()->startedAt);
        $activity->recordDetail($effort);
        $activity->recordDetail($overlapping);

        self::assertTrue(
            $activity->snapshot()->lastDetailFinishedAt?->equals($overlapping->interval()->finishedAt),
        );
        self::assertSame(
            'f784f6d0-1337-4a8a-92c8-001122334455',
            $effort->segmentId,
        );
        self::assertSame('Harju climb', $effort->name);
        self::assertSame('end', $effort->status);
        self::assertSame('cycling', $effort->sport);
        self::assertSame('road', $effort->subSport);
        self::assertSame('garmin', $effort->manufacturer);
        self::assertCount(1, $effort->readings());
    }

    public function testRejectsSubSportWithoutSport(): void
    {
        $this->expectException(
            InvalidSegmentEffort::class,
        );

        SegmentEffort::create(
            interval: $this->interval(),
            subSport: 'road',
        );
    }

    public function testRejectsInvalidStatusIdentifier(): void
    {
        $this->expectException(
            InvalidSegmentEffort::class,
        );

        $this->expectExceptionMessage(
            'status must use canonical snake_case',
        );

        SegmentEffort::create(
            interval: $this->interval(),
            status: 'Completed!',
        );
    }

    public function testRejectsDuplicateMeasurementTypes(): void
    {
        $reading = $this->reading(
            type: 'total_distance',
            value: 1_234.5,
            unit: 'm',
        );

        $this->expectException(
            InvalidSegmentEffort::class,
        );

        SegmentEffort::create(
            interval: $this->interval(),
            readings: [$reading, $reading],
        );
    }

    private function interval(): ActivityInterval
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );
        $finishedAt = $this->instant(
            '2026-01-15T10:35:00Z',
        );

        return ActivityInterval::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
        );
    }

    private function reading(
        string $type,
        int|float $value,
        string $unit,
    ): MeasurementReading {
        return MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    $type,
                ),
                value: $value,
                unit: MeasurementUnit::fromSymbol($unit),
            ),
            source: MeasurementSource::unknown(),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
