<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Import\Handler;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivitySummaryItem;
use Youmad\Endurance\Activity\Application\Import\Handler\ActivitySummaryItemHandler;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\ApplyActivitySummary;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\PositionMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class ActivitySummaryItemHandlerTest extends TestCase
{
    public function testAppliesSummaryWithImportedTimelineResolution(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:00:00Z'),
        );
        $activity->recordObservation(
            ActivityObservation::create(
                $this->instant('2026-01-15T10:30:01Z'),
                new PositionMeasurement(
                    new Coordinate(
                        latitude: 59.4369,
                        longitude: 24.7535,
                    ),
                ),
            ),
        );
        $activity->recordSession(
            ActivitySession::create(
                startedAt: $this->instant(
                    '2026-01-15T10:00:00Z',
                ),
                finishedAt: $this->instant(
                    '2026-01-15T10:30:00.545000Z',
                ),
                timerDuration: Duration::fromMicroseconds(
                    1_800_545_000,
                ),
            ),
        );

        $handler = new ActivitySummaryItemHandler(
            new ApplyActivitySummary(
                activities: $this->createStub(
                    ActivityRepository::class,
                ),
                transaction: $this->createStub(
                    ActivityTransaction::class,
                ),
            ),
        );

        self::assertTrue(
            $handler->handle(
                context: new ActivityImportContext(
                    activity: $activity,
                    generationId: ActivityImportGenerationId::generate(),
                ),
                item: new ActivitySummaryItem(
                    summary: ActivitySummary::create(
                        reportedAt: $this->instant(
                            '2026-01-15T10:30:15Z',
                        ),
                        timerDuration: Duration::fromMicroseconds(
                            1_800_545_000,
                        ),
                        sessionCount: 1,
                    ),
                    timelineResolution: TemporalResolution::Second,
                ),
            ),
        );

        self::assertTrue(
            $activity->finishedAt?->equals(
                $this->instant('2026-01-15T10:30:01Z'),
            ),
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
