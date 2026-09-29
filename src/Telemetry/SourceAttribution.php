<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Telemetry;

enum SourceAttribution: string
{
    case Explicit = 'explicit';
    case Inferred = 'inferred';
    case Unknown = 'unknown';
}
