<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\ActivityLifecycleItem;
use Youmad\Endurance\Activity\Application\UseCase\ApplyActivityLifecycleEvent;

final readonly class ActivityLifecycleItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private ApplyActivityLifecycleEvent $events,
    ) {
    }

    public function itemClass(): string
    {
        return ActivityLifecycleItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof ActivityLifecycleItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ActivityLifecycleItem::class, $item::class));
        }

        return $this->events->apply(
            activity: $context->activity,
            event: $item,
        );
    }
}
