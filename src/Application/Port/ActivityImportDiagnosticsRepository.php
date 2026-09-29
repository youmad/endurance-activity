<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Read\StaleActivityImportCandidate;

interface ActivityImportDiagnosticsRepository
{
    /**
     * @return list<StaleActivityImportCandidate>
     */
    public function findStaleProcessing(
        int $staleAfterSeconds,
        int $limit,
    ): array;
}
