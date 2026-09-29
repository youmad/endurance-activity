<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

enum ActivityLifecycleAction: string
{
    case Start = 'start';
    case Pause = 'pause';
    case Resume = 'resume';
    case Finish = 'finish';
}
