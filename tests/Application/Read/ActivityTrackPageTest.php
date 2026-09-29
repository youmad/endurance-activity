<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Read;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackCursor;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackPage;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackPointReadModel;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityTrackPageTest extends TestCase
{
    public function testAcceptsStrictlyOrderedPointsAndFinalCursor(): void
    {
        $first = $this->point('2026-08-05T10:00:00Z', 10);
        $second = $this->point('2026-08-05T10:00:00Z', 11);

        $page = new ActivityTrackPage(
            activityId: ActivityId::generate(),
            points: [$first, $second],
            nextCursor: $second->cursor,
        );

        self::assertSame(2, $page->count());
        self::assertSame([$first, $second], $page->points());
    }

    public function testRejectsDuplicateOrDescendingCursorOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityTrackPage(
            activityId: ActivityId::generate(),
            points: [
                $this->point('2026-08-05T10:00:01Z', 2),
                $this->point('2026-08-05T10:00:00Z', 3),
            ],
            nextCursor: null,
        );
    }

    public function testRejectsCursorThatDoesNotReferenceFinalPoint(): void
    {
        $first = $this->point('2026-08-05T10:00:00Z', 1);
        $second = $this->point('2026-08-05T10:00:01Z', 2);

        $this->expectException(\InvalidArgumentException::class);

        new ActivityTrackPage(
            activityId: ActivityId::generate(),
            points: [$first, $second],
            nextCursor: $first->cursor,
        );
    }

    private function point(
        string $timestamp,
        int $observationId,
    ): ActivityTrackPointReadModel {
        $instant = Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($timestamp),
        );

        return new ActivityTrackPointReadModel(
            cursor: new ActivityTrackCursor(
                observedAt: $instant,
                observationId: $observationId,
            ),
            timestamp: $instant,
            position: null,
            altitude: null,
            distance: null,
            speed: null,
            heartRate: null,
            cadence: null,
            power: null,
            temperature: null,
        );
    }
}
