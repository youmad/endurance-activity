<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

interface StagedActivityObservationBatchWriter
{
    /**
     * Persists one invisible batch as one atomic short operation.
     *
     * @param non-empty-list<ActivityObservation> $observations
     */
    public function appendBatch(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        array $observations,
    ): void;
}
