<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import\Handler;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemHandler;
use Youmad\Endurance\Activity\Application\Import\SessionItem;
use Youmad\Endurance\Activity\Application\Port\StagedActivitySessionWriter;

final readonly class SessionItemHandler implements ActivityImportItemHandler
{
    public function __construct(
        private StagedActivitySessionWriter $sessions,
    ) {
    }

    public function itemClass(): string
    {
        return SessionItem::class;
    }

    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        if (!$item instanceof SessionItem) {
            throw new \LogicException(sprintf('Expected %s, got %s.', SessionItem::class, $item::class));
        }

        $context->activity->recordSession($item->session);

        $this->sessions->append(
            generationId: $context->generationId,
            activityId: $context->activity->id,
            session: $item->session,
        );

        return true;
    }
}
