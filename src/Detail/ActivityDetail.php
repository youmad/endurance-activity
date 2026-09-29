<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail;

interface ActivityDetail
{
    public function interval(): ActivityInterval;
}
