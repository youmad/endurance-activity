<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Batch;

use Youmad\Endurance\Activity\ValueObject\ActivityId;

/**
 * @template TItem of object
 */
final class ActivityImportBatchBuffer
{
    /**
     * @var array<
     *     string,
     *     array{
     *         activityId: ActivityId,
     *         items: list<TItem>
     *     }
     * >
     */
    private array $buffers = [];

    /**
     * @param \Closure(ActivityId, non-empty-list<TItem>): void $flushBatch
     */
    public function __construct(
        private readonly int $batchSize,
        private readonly \Closure $flushBatch,
    ) {
        if (1 > $batchSize) {
            throw new \InvalidArgumentException('Activity import batch size must be positive.');
        }
    }

    /**
     * @param TItem $item
     */
    public function append(
        ActivityId $activityId,
        object $item,
    ): void {
        $key = $activityId->toString();

        $this->buffers[$key] ??= [
            'activityId' => $activityId,
            'items' => [],
        ];

        $this->buffers[$key]['items'][] = $item;

        if (
            $this->batchSize
            <= count($this->buffers[$key]['items'])
        ) {
            $this->flushBuffer($key);
        }
    }

    public function flush(): void
    {
        foreach (array_keys($this->buffers) as $key) {
            $this->flushBuffer($key);
        }
    }

    public function discard(): void
    {
        $this->buffers = [];
    }

    public function pendingCount(): int
    {
        $count = 0;

        foreach ($this->buffers as $buffer) {
            $count += count($buffer['items']);
        }

        return $count;
    }

    private function flushBuffer(string $key): void
    {
        $buffer = $this->buffers[$key] ?? null;

        if (
            null === $buffer
            || [] === $buffer['items']
        ) {
            return;
        }

        ($this->flushBatch)(
            $buffer['activityId'],
            $buffer['items'],
        );

        unset($this->buffers[$key]);
    }
}
