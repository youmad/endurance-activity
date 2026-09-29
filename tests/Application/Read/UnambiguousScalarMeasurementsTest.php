<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Read;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Read\ActivityScalarMeasurement;
use Youmad\Endurance\Activity\Application\Read\UnambiguousScalarMeasurements;

final class UnambiguousScalarMeasurementsTest extends TestCase
{
    public function testKeepsUniqueTypesAndNeverSelectsFromRepeatedTypes(): void
    {
        $speed = new ActivityScalarMeasurement('speed', 8, 'm/s');
        $readings = [
            new ActivityScalarMeasurement('heart_rate', 140, 'bpm'),
            $speed,
            new ActivityScalarMeasurement('heart_rate', 145, 'bpm'),
            new ActivityScalarMeasurement('heart_rate', 150, 'bpm'),
            new ActivityScalarMeasurement('altitude', 100, 'm'),
            new ActivityScalarMeasurement('altitude', 300, 'ft'),
        ];

        self::assertSame(['speed' => $speed], UnambiguousScalarMeasurements::byType($readings));
        self::assertSame(['speed' => $speed], UnambiguousScalarMeasurements::byType(array_reverse($readings)));
    }

    public function testEqualReadingsRemainAmbiguous(): void
    {
        $reading = new ActivityScalarMeasurement('heart_rate', 140, 'bpm');
        self::assertSame([], UnambiguousScalarMeasurements::byType([$reading, $reading]));
        self::assertSame([], UnambiguousScalarMeasurements::byType([]));
    }
}
