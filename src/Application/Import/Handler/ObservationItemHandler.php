<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\ObservationItem;
use Youmad\Endurance\Activity\Application\Port\StagedActivityObservationWriter;

final readonly class ObservationItemHandler implements ActivityImportItemHandler, ActivityImportBatchParticipant
{
    public function __construct(
        private StagedActivityObservationWriter $observations,
    ) {
    }

    public function itemClass(): string
    {
        return ObservationItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof ObservationItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ObservationItem::class, $item::class));
        }

        $context->activity->recordObservation(
            $item->observation,
        );

        $this->observations->append(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            observation: $item->observation,
        );

        return true;
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
