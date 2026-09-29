<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Device\DeviceStatusObservation;

final readonly class DeviceStatusItem implements ActivityImportItem
{
    public function __construct(
        public DeviceStatusObservation $observation,
    ) {
    }
}
