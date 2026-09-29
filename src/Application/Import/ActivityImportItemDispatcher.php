<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Exception\DuplicateActivityImportItemHandler;
use Youmad\Endurance\Activity\Exception\UnsupportedActivityImportItem;

final readonly class ActivityImportItemDispatcher
{
    /**
     * @var array<
     *     class-string<ActivityImportItem>,
     *     ActivityImportItemHandler
     * >
     */
    private array $handlers;

    /** @var list<ActivityImportBatchParticipant> */
    private array $batchParticipants;

    public function __construct(
        ActivityImportItemHandler ...$handlers,
    ) {
        $indexedHandlers = [];
        $batchParticipants = [];

        foreach ($handlers as $handler) {
            $itemClass = $handler->itemClass();

            if (isset($indexedHandlers[$itemClass])) {
                throw DuplicateActivityImportItemHandler::forItemClass($itemClass);
            }

            $indexedHandlers[$itemClass] = $handler;

            if ($handler instanceof ActivityImportBatchParticipant) {
                $batchParticipants[] = $handler;
            }
        }

        $this->handlers = $indexedHandlers;
        $this->batchParticipants = $batchParticipants;
    }

    /** @return bool Whether the aggregate was changed. */
    public function dispatch(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool {
        $handler = $this->handlers[$item::class]
            ?? throw UnsupportedActivityImportItem::forItem($item);

        return $handler->handle(
            context: $context,
            item: $item,
        );
    }

    public function flush(): void
    {
        foreach ($this->batchParticipants as $participant) {
            $participant->flush();
        }
    }

    public function discard(): void
    {
        foreach ($this->batchParticipants as $participant) {
            $participant->discard();
        }
    }
}
