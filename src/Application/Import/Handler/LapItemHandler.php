<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\LapItem;
use Youmad\Endurance\Activity\Application\Port\StagedLapWriter;

final readonly class LapItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private StagedLapWriter $laps,
    ) {
    }

    public function itemClass(): string
    {
        return LapItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof LapItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', LapItem::class, $item::class));
        }

        $context->activity->recordLap($item->lap);

        $this->laps->append(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            lap: $item->lap,
        );

        return true;
    }
}
