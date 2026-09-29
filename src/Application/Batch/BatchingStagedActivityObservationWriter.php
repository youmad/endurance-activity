<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Batch;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Port\StagedActivityObservationBatchWriter;
use Youmad\Endurance\Activity\Application\Port\StagedActivityObservationWriter;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final class BatchingStagedActivityObservationWriter implements StagedActivityObservationWriter, ActivityImportBatchParticipant
{
    /** @var ActivityImportGenerationBatchBuffer<ActivityObservation> */
    private ActivityImportGenerationBatchBuffer $buffer;

    /** @param positive-int $batchSize */
    public function __construct(
        StagedActivityObservationBatchWriter $writer,
        int $batchSize,
    ) {
        $this->buffer = new ActivityImportGenerationBatchBuffer(
            batchSize: $batchSize,
            flushBatch: static function (
                ActivityImportGenerationId $generationId,
                ActivityId $activityId,
                array $observations,
            ) use ($writer): void {
                $writer->appendBatch(
                    generationId: $generationId,
                    activityId: $activityId,
                    observations: $observations,
                );
            },
        );
    }

    public function append(
        ActivityImportGenerationId $generationId,
        ActivityId $activityId,
        ActivityObservation $observation,
    ): void {
        $this->buffer->append(
            generationId: $generationId,
            activityId: $activityId,
            item: $observation,
        );
    }

    public function flush(): void
    {
        $this->buffer->flush();
    }

    public function discard(): void
    {
        $this->buffer->discard();
    }

    public function pendingCount(): int
    {
        return $this->buffer->pendingCount();
    }
}
