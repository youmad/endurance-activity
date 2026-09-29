<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Foundation\ValueObject\Instant;

final readonly class ActivityLifecycleItem implements ActivityImportItem
{
    public function __construct(
        public ActivityLifecycleAction $action,
        public Instant $occurredAt,
    ) {
    }
}
