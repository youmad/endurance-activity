<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

use Youmad\Endurance\Activity\Application\Import\ActivityImportGenerationClaim;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

interface ActivityImportGenerationRepository
{
    /**
     * Atomically claims an import generation.
     *
     * At most one concurrent caller may receive an acquired claim for the same
     * activity and idempotency key. An active duplicate returns
     * alreadyCompleted(). An unfinished concurrent duplicate must be rejected.
     * A failed generation may be reclaimed only after every staged row belonging
     * to it has been removed atomically.
     *
     * The current aggregate model supports one successful imported generation
     * per activity. A different key for an already imported activity must be
     * rejected instead of silently replacing aggregate state.
     */
    public function claim(
        ActivityId $activityId,
        ActivityImportIdempotencyKey $idempotencyKey,
    ): ActivityImportGenerationClaim;

    /**
     * Atomically marks the generation active and attaches it to its activity.
     */
    public function activate(
        ActivityImportGenerationId $generationId,
    ): void;

    /**
     * Marks an unfinished generation failed. Its rows remain invisible.
     */
    public function fail(
        ActivityImportGenerationId $generationId,
    ): void;
}
