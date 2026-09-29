<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail;

use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

interface SequentialActivityDetail extends ActivityDetail
{
    /**
     * @return non-empty-string
     */
    public function sequenceName(): string;

    public function timelineResolution(): TemporalResolution;
}
