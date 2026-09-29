<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Session;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidActivitySession;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivitySessionTest extends TestCase
{
    public function testPreservesTimingDisciplinePositionsAndReadings(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );
        $finishedAt = $this->instant(
            '2026-01-15T11:00:00Z',
        );
        $reading = MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString(
                    'total_distance',
                ),
                value: 15_000,
                unit: MeasurementUnit::fromSymbol('m'),
            ),
            source: MeasurementSource::unknown(),
        );

        $session = ActivitySession::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::fromMicroseconds(
                1_700_000_000,
            ),
            sport: 'cycling',
            subSport: 'road',
            startPosition: new Coordinate(59.4, 24.7),
            endPosition: new Coordinate(59.5, 24.8),
            readings: [$reading],
        );

        self::assertSame(
            1_800_000_000,
            $session->elapsedDuration()->toMicroseconds(),
        );
        self::assertSame('cycling', $session->sport);
        self::assertSame('road', $session->subSport);
        self::assertSame(
            TemporalResolution::Microsecond,
            $session->timelineResolution,
        );
        self::assertSame([$reading], $session->readings());
    }

    public function testRejectsSubSportWithoutSport(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );
        $finishedAt = $this->instant(
            '2026-01-15T11:00:00Z',
        );

        $this->expectException(
            InvalidActivitySession::class,
        );

        ActivitySession::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
            subSport: 'road',
        );
    }

    public function testRejectsDuplicateMeasurementTypes(): void
    {
        $startedAt = $this->instant(
            '2026-01-15T10:30:00Z',
        );
        $finishedAt = $this->instant(
            '2026-01-15T11:00:00Z',
        );

        $first = $this->reading('total_distance', 100);
        $second = $this->reading('total_distance', 200);

        $this->expectException(
            InvalidActivitySession::class,
        );

        ActivitySession::create(
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            timerDuration: Duration::between(
                $startedAt,
                $finishedAt,
            ),
            readings: [$first, $second],
        );
    }

    private function reading(
        string $type,
        int|float $value,
    ): MeasurementReading {
        return MeasurementReading::reported(
            measurement: new ScalarMeasurement(
                measurementType: MeasurementType::fromString($type),
                value: $value,
                unit: MeasurementUnit::none(),
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
