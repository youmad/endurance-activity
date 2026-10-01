<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

enum ActivityLifecycleAction: string
{
    case Start = 'start';
    case TimerStart = 'timer_start';
    case Pause = 'pause';
    case Resume = 'resume';
    case Finish = 'finish';
}
