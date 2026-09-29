<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final readonly class ActivitySummaryItem implements ActivityImportItem
{
    public TemporalResolution $timelineResolution;

    public function __construct(
        public ActivitySummary $summary,
        ?TemporalResolution $timelineResolution = null,
    ) {
        $this->timelineResolution = $timelineResolution
            ?? TemporalResolution::Microsecond;
    }
}
