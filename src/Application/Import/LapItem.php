<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

use Youmad\Endurance\Activity\ValueObject\Lap;

final readonly class LapItem implements ActivityImportItem
{
    public function __construct(
        public Lap $lap,
        public ?int $index = null,
        public ?int $firstLengthIndex = null,
        public ?int $lengthCount = null,
        public ?int $activeLengthCount = null,
    ) {
    }
}
