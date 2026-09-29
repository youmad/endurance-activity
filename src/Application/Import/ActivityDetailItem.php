<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Detail\ActivityDetail;

final readonly class ActivityDetailItem implements ActivityImportItem
{
    public function __construct(
        public ActivityDetail $detail,
        public ?int $index = null,
    ) {
    }
}
