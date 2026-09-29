<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Import\ActivityImportContext;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;
use Youmad\Endurance\Activity\Application\Import\ActivityImportItemDispatcher;
use Youmad\Endurance\Activity\Application\Import\ActivityImportResult;
use Youmad\Endurance\Activity\Application\Port\ActivityRepository;
use Youmad\Endurance\Activity\Exception\ActivityImportFailureFinalizationFailed;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

final readonly class ImportActivityStream
{
    public function __construct(
        private ActivityRepository $activities,
        private ActivityImportGenerationCoordinator $generations,
        private ActivityImportItemDispatcher $dispatcher,
    ) {
    }

    /**
     * Imports one complete item stream through an invisible generation.
     *
     * Full staged batches may be committed while the stream is being consumed.
     * The final partial batches are flushed before the short activation
     * transaction. Only that final transaction persists the aggregate and makes
     * the generation visible.
     *
     * @param iterable<ActivityImportItem> $items
     * @param \Closure(): void|null        $finalizeImport
     */
    public function handle(
        ActivityId $activityId,
        ActivityImportIdempotencyKey $idempotencyKey,
        iterable $items,
        ?\Closure $finalizeImport = null,
    ): ActivityImportResult {
        $claim = $this->generations->claim(
            activityId: $activityId,
            idempotencyKey: $idempotencyKey,
        );

        if (!$claim->isAcquired()) {
            return ActivityImportResult::alreadyImported(
                $claim->generationId,
            );
        }

        try {
            $activity = $this->activities->get($activityId);
            $context = new ActivityImportContext(
                activity: $activity,
                generationId: $claim->generationId,
            );
            $aggregateChanged = false;

            foreach ($items as $item) {
                $aggregateChanged = $this
                    ->dispatcher
                    ->dispatch(
                        context: $context,
                        item: $item,
                    )
                    || $aggregateChanged;
            }

            $this->dispatcher->flush();

            $this->generations->activate(
                generationId: $claim->generationId,
                persistAggregate: function () use (
                    $activity,
                    $aggregateChanged,
                ): void {
                    if ($aggregateChanged) {
                        $this->activities->save($activity);
                    }
                },
                finalizeImport: $finalizeImport,
            );
        } catch (\Throwable $exception) {
            $this->dispatcher->discard();

            try {
                $this->generations->fail(
                    $claim->generationId,
                );
            } catch (\Throwable $finalizationFailure) {
                throw ActivityImportFailureFinalizationFailed::because(importFailure: $exception, finalizationFailure: $finalizationFailure);
            }

            throw $exception;
        }

        return ActivityImportResult::imported(
            $claim->generationId,
        );
    }
}
