<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityDetailItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Port\StagedActivityDetailWriter;

final readonly class ActivityDetailItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private StagedActivityDetailWriter $details,
    ) {
    }

    public function itemClass(): string
    {
        return ActivityDetailItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof ActivityDetailItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ActivityDetailItem::class, $item::class));
        }

        $context->activity->recordDetail($item->detail);

        $this->details->append(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            detail: $item->detail,
        );

        return true;
    }
}
