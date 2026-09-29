<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

enum ActivityImportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Recovering = 'recovering';
    case Completed = 'completed';
    case Failed = 'failed';
}
