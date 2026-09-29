<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\ActivitySummaryItem;
use Youmad\Endurance\Activity\Application\UseCase\ApplyActivitySummary;

final readonly class ActivitySummaryItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private ApplyActivitySummary $summaries,
    ) {
    }

    public function itemClass(): string
    {
        return ActivitySummaryItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof ActivitySummaryItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ActivitySummaryItem::class, $item::class));
        }

        $this->summaries->apply(
            activity: $context->activity,
            summary: $item->summary,
            timelineResolution: $item->timelineResolution,
        );

        return true;
    }
}
