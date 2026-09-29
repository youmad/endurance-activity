<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

interface Measurement
{
    public function type(): MeasurementType;
}
