<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Batch;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Port\ActivityObservationBatchWriter;
use Youmad\Endurance\Activity\Application\Port\ActivityObservationWriter;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class BatchingActivityObservationWriter implements ActivityObservationWriter, ActivityImportBatchParticipant
{
    /** @var ActivityImportBatchBuffer<ActivityObservation> */
    private ActivityImportBatchBuffer $buffer;

    /** @param positive-int $batchSize */
    public function __construct(
        ActivityObservationBatchWriter $writer,
        int $batchSize,
    ) {
        $this->buffer = new ActivityImportBatchBuffer(
            batchSize: $batchSize,
            flushBatch: static function (
                ActivityId $activityId,
                array $observations,
            ) use ($writer): void {
                $writer->appendBatch(
                    activityId: $activityId,
                    observations: $observations,
                );
            },
        );
    }

    public function append(
        ActivityId $activityId,
        ActivityObservation $observation,
    ): void {
        $this->buffer->append(
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
