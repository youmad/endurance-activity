<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Detail\Pool;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Detail\ActivityInterval;
use Youmad\Endurance\Activity\Detail\Pool\PoolLength;
use Youmad\Endurance\Activity\Detail\Pool\PoolLengthType;
use Youmad\Endurance\Activity\Exception\InvalidPoolLength;
use Youmad\Endurance\Activity\Telemetry\MeasurementReading;
use Youmad\Endurance\Activity\Telemetry\MeasurementSource;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class PoolLengthTest extends TestCase
{
    public function testCreatesSequentialPoolLengthWithStrokeAndReadings(): void
    {
        $length = PoolLength::create(
            interval: ActivityInterval::create(
                startedAt: $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:25.500000Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    25_250_000,
                ),
            ),
            type: PoolLengthType::Active,
            stroke: 'freestyle',
            readings: [
                $this->reading('stroke_count', 18, 'strokes'),
            ],
        );

        self::assertSame(
            25_500_000,
            $length
                ->interval()
                ->elapsedDuration()
                ->toMicroseconds(),
        );

        self::assertSame(
            PoolLengthType::Active,
            $length->type,
        );

        self::assertSame(
            'freestyle',
            $length->stroke,
        );

        self::assertSame(
            PoolLength::SEQUENCE_NAME,
            $length->sequenceName(),
        );

        self::assertCount(
            1,
            $length->readings(),
        );
    }

    public function testIdleLengthCannotDeclareStroke(): void
    {
        $this->expectException(
            InvalidPoolLength::class,
        );

        PoolLength::create(
            interval: ActivityInterval::create(
                startedAt: $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:10Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    10_000_000,
                ),
            ),
            type: PoolLengthType::Idle,
            stroke: 'freestyle',
        );
    }

    public function testRejectsDuplicateMeasurementType(): void
    {
        $this->expectException(
            InvalidPoolLength::class,
        );

        $this->expectExceptionMessage(
            'stroke_count more than once',
        );

        PoolLength::create(
            interval: ActivityInterval::create(
                startedAt: $this->instant(
                    '2026-01-15T10:30:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:25Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    25_000_000,
                ),
            ),
            type: PoolLengthType::Active,
            readings: [
                $this->reading('stroke_count', 18, 'strokes'),
                $this->reading('stroke_count', 19, 'strokes'),
            ],
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
