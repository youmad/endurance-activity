<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

interface ActivityImportItemHandler
{
    /** @return class-string<ActivityImportItem> */
    public function itemClass(): string;

    /** @return bool Whether the aggregate was changed. */
    public function handle(
        ActivityImportContext $context,
        ActivityImportItem $item,
    ): bool;
}
