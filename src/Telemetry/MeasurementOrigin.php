<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

enum MeasurementOrigin: string
{
    case Reported = 'reported';
    case Derived = 'derived';
}
