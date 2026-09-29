<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Detail\Pool;

enum PoolLengthType: string
{
    case Idle = 'idle';
    case Active = 'active';
}
