<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Batch;

use Youmad\Endurance\Activity\Application\Import\ActivityImportBatchParticipant;
use Youmad\Endurance\Activity\Application\Port\DeviceStatusObservationBatchWriter;
use Youmad\Endurance\Activity\Application\Port\DeviceStatusObservationWriter;
use Youmad\Endurance\Activity\Device\DeviceStatusObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class BatchingDeviceStatusObservationWriter implements DeviceStatusObservationWriter, ActivityImportBatchParticipant
{
    /** @var ActivityImportBatchBuffer<DeviceStatusObservation> */
    private ActivityImportBatchBuffer $buffer;

    /** @param positive-int $batchSize */
    public function __construct(
        DeviceStatusObservationBatchWriter $writer,
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
        DeviceStatusObservation $observation,
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
