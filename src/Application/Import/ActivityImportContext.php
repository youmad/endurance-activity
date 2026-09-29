<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final readonly class ActivityImportContext
{
    public function __construct(
        public Activity $activity,
        public ActivityImportGenerationId $generationId,
    ) {
    }
}
