<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Telemetry\ActivityObservation;

final readonly class ObservationItem implements ActivityImportItem
{
    public function __construct(
        public ActivityObservation $observation,
    ) {
    }
}
