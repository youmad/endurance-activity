<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Import;

enum ActivityImportOutcome
{
    case Imported;
    case AlreadyImported;
}
