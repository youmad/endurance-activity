<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\TrackPoint;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class TrackPointTest extends TestCase
{
    public function testCanCreateTrackPoint(): void
    {
        $timestamp = Instant::fromDateTimeImmutable(
            new \DateTimeImmutable('2026-01-15T10:30:45Z'),
        );

        $coordinate = new Coordinate(
            59.4369,
            24.7535,
        );

        $point = new TrackPoint(
            $timestamp,
            $coordinate,
        );

        self::assertTrue(
            $point->timestamp->equals($timestamp),
        );

        self::assertSame(
            $coordinate,
            $point->coordinate,
        );
    }
}
