<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Import;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemDispatcher;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\ObservationItem;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\Exception\DuplicateActivityImportItemHandler;
use Youmad\Endurance\Activity\Exception\UnsupportedActivityImportItem;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\Telemetry\MeasurementType;
use Youmad\Endurance\Activity\Telemetry\MeasurementUnit;
use Youmad\Endurance\Activity\Telemetry\ScalarMeasurement;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Foundation\ValueObject\Instant;

final class ActivityImportItemDispatcherTest extends TestCase
{
    public function testDispatchesExactItemWithImportContext(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );
        $generationId = ActivityImportGenerationId::generate();
        $context = new ActivityImportContext(
            activity: $activity,
            generationId: $generationId,
        );
        $item = new ObservationItem(
            ActivityObservation::create(
                timestamp: $this->instant('2026-01-15T10:30:01Z'),
                measurements: new ScalarMeasurement(
                    measurementType: MeasurementType::fromString(
                        'heart_rate',
                    ),
                    value: 150,
                    unit: MeasurementUnit::fromSymbol('bpm'),
                ),
            ),
        );

        $handler = new class implements ActivityImportItemHandler {
            public ?ActivityImportContext $context = null;
            public ?ActivityImportItem $item = null;

            public function itemClass(): string
            {
                return ObservationItem::class;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                $this->context = $context;
                $this->item = $item;

                return true;
            }
        };

        $changed = (new ActivityImportItemDispatcher($handler))
            ->dispatch(
                context: $context,
                item: $item,
            );

        self::assertSame($context, $handler->context);
        self::assertSame($item, $handler->item);
        self::assertTrue($changed);
    }

    public function testFlushesAndDiscardsBatchParticipants(): void
    {
        $handler = new class implements ActivityImportItemHandler, ActivityImportBatchParticipant {
            public int $flushes = 0;
            public int $discards = 0;

            public function itemClass(): string
            {
                return ObservationItem::class;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                return false;
            }

            public function flush(): void
            {
                ++$this->flushes;
            }

            public function discard(): void
            {
                ++$this->discards;
            }
        };

        $dispatcher = new ActivityImportItemDispatcher($handler);
        $dispatcher->flush();
        $dispatcher->discard();

        self::assertSame(1, $handler->flushes);
        self::assertSame(1, $handler->discards);
    }

    public function testRejectsDuplicateHandlers(): void
    {
        $factory = static fn (): ActivityImportItemHandler => new class implements ActivityImportItemHandler {
            public function itemClass(): string
            {
                return ObservationItem::class;
            }

            public function handle(
                ActivityImportContext $context,
                ActivityImportItem $item,
            ): bool {
                return false;
            }
        };

        $this->expectException(
            DuplicateActivityImportItemHandler::class,
        );

        new ActivityImportItemDispatcher($factory(), $factory());
    }

    public function testRejectsUnsupportedItem(): void
    {
        $activity = Activity::start(
            $this->instant('2026-01-15T10:30:00Z'),
        );

        $this->expectException(
            UnsupportedActivityImportItem::class,
        );

        (new ActivityImportItemDispatcher())->dispatch(
            context: new ActivityImportContext(
                activity: $activity,
                generationId: ActivityImportGenerationId::generate(),
            ),
            item: new class implements ActivityImportItem {},
        );
    }

    private function instant(string $value): Instant
    {
        return Instant::fromDateTimeImmutable(
            new \DateTimeImmutable($value),
        );
    }
}
