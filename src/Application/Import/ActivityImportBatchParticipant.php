<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

interface ActivityImportBatchParticipant
{
    /**
     * Persists all currently buffered items.
     */
    public function flush(): void;

    /**
     * Drops all currently buffered items without persisting them.
     */
    public function discard(): void;
}
