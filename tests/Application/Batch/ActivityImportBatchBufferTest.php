<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\ActivityImportBatchBuffer;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class ActivityImportBatchBufferTest extends TestCase
{
    public function testRejectsNonPositiveBatchSize(): void
    {
        $this->expectException(
            \InvalidArgumentException::class,
        );

        new ActivityImportBatchBuffer(
            batchSize: 0,
            flushBatch: static function (): void {
            },
        );
    }

    public function testFlushesFullBatchAndKeepsRemainder(): void
    {
        $activityId = ActivityId::generate();
        $first = new \stdClass();
        $second = new \stdClass();
        $third = new \stdClass();
        /** @var \ArrayObject<int, array{activityId: ActivityId, items: non-empty-list<object>}> $flushed */
        $flushed = new \ArrayObject();

        $buffer = new ActivityImportBatchBuffer(
            batchSize: 2,
            flushBatch: static function (
                ActivityId $flushedActivityId,
                array $items,
            ) use ($flushed): void {
                $flushed[] = [
                    'activityId' => $flushedActivityId,
                    'items' => $items,
                ];
            },
        );

        $buffer->append($activityId, $first);

        self::assertSame(1, $buffer->pendingCount());
        self::assertSame([], $flushed->getArrayCopy());

        $buffer->append($activityId, $second);

        self::assertSame(0, $buffer->pendingCount());
        self::assertCount(1, $flushed);
        self::assertTrue(
            $activityId->equals($flushed[0]['activityId']),
        );
        self::assertSame(
            [$first, $second],
            $flushed[0]['items'],
        );

        $buffer->append($activityId, $third);
        $buffer->flush();

        self::assertSame(0, $buffer->pendingCount());
        self::assertCount(2, $flushed);
        self::assertSame(
            [$third],
            $flushed[1]['items'],
        );
    }

    public function testGroupsItemsByActivity(): void
    {
        $firstActivityId = ActivityId::generate();
        $secondActivityId = ActivityId::generate();
        $firstItem = new \stdClass();
        $secondItem = new \stdClass();
        $flushed = [];

        $buffer = new ActivityImportBatchBuffer(
            batchSize: 10,
            flushBatch: static function (
                ActivityId $activityId,
                array $items,
            ) use (&$flushed): void {
                $flushed[$activityId->toString()] = $items;
            },
        );

        $buffer->append($firstActivityId, $firstItem);
        $buffer->append($secondActivityId, $secondItem);
        $buffer->flush();

        self::assertSame(
            [$firstItem],
            $flushed[$firstActivityId->toString()],
        );
        self::assertSame(
            [$secondItem],
            $flushed[$secondActivityId->toString()],
        );
    }

    public function testKeepsItemsBufferedWhenBatchWriteFails(): void
    {
        $activityId = ActivityId::generate();
        $buffer = new ActivityImportBatchBuffer(
            batchSize: 2,
            flushBatch: static function (): void {
                throw new \RuntimeException('Batch write failed.');
            },
        );

        $buffer->append($activityId, new \stdClass());

        try {
            $buffer->append($activityId, new \stdClass());

            self::fail('Expected batch write to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Batch write failed.',
                $exception->getMessage(),
            );
        }

        self::assertSame(2, $buffer->pendingCount());

        $buffer->discard();

        self::assertSame(0, $buffer->pendingCount());
    }
}
