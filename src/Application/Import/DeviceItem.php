<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Device\ActivityDevice;

final readonly class DeviceItem implements ActivityImportItem
{
    public function __construct(
        public ActivityDevice $device,
    ) {
    }
}
