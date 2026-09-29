<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Recovery;

enum ActivityImportRecoveryAction: string
{
    case Requeued = 'requeued';
    case Failed = 'failed';
}
