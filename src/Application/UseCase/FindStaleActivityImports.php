<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityImportDiagnosticsRepository;
use Youmad\Endurance\Activity\Application\Read\StaleActivityImportCandidate;

final readonly class FindStaleActivityImports
{
    public function __construct(
        private ActivityImportDiagnosticsRepository $imports,
    ) {
    }

    /** @return list<StaleActivityImportCandidate> */
    public function find(
        int $staleAfterSeconds,
        int $limit,
    ): array {
        return $this->imports->findStaleProcessing(
            staleAfterSeconds: $staleAfterSeconds,
            limit: $limit,
        );
    }
}
