<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\Session\ActivitySession;

final readonly class SessionItem implements ActivityImportItem
{
    public function __construct(
        public ActivitySession $session,
        public ?int $firstLapIndex = null,
        public ?int $lapCount = null,
        public ?int $lengthCount = null,
        public ?int $activeLengthCount = null,
    ) {
    }
}
