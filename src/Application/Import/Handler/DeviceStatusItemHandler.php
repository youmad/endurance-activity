<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\DeviceStatusItem;
use Youmad\Endurance\Activity\Application\Port\StagedDeviceStatusObservationWriter;

final readonly class DeviceStatusItemHandler implements ActivityImportItemHandler, ActivityImportBatchParticipant
{
    public function __construct(
        private StagedDeviceStatusObservationWriter $observations,
    ) {
    }

    public function itemClass(): string
    {
        return DeviceStatusItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof DeviceStatusItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeviceStatusItem::class, $item::class));
        }

        $this->observations->append(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            observation: $item->observation,
        );

        return false;
    }

    public function flush(): void
    {
        if ($this->observations instanceof ActivityImportBatchParticipant) {
            $this->observations->flush();
        }
    }

    public function discard(): void
    {
        if ($this->observations instanceof ActivityImportBatchParticipant) {
            $this->observations->discard();
        }
    }
}
