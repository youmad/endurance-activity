<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\DeviceItem;
use Youmad\Endurance\Activity\Application\Port\StagedActivityDeviceWriter;

final readonly class DeviceItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private StagedActivityDeviceWriter $devices,
    ) {
    }

    public function itemClass(): string
    {
        return DeviceItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof DeviceItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeviceItem::class, $item::class));
        }

        $this->devices->save(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            device: $item->device,
        );

        return false;
    }
}
