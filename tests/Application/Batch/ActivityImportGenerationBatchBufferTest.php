<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\Batch;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Batch\ActivityImportGenerationBatchBuffer;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;

final class ActivityImportGenerationBatchBufferTest extends TestCase
{
    public function testRejectsNonPositiveBatchSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ActivityImportGenerationBatchBuffer(
            batchSize: 0,
            flushBatch: static function (): void {},
        );
    }

    public function testGroupsByGenerationAndActivity(): void
    {
        $firstGeneration = ActivityImportGenerationId::generate();
        $secondGeneration = ActivityImportGenerationId::generate();
        $firstActivity = ActivityId::generate();
        $secondActivity = ActivityId::generate();
        $first = new \stdClass();
        $second = new \stdClass();
        $third = new \stdClass();
        $batches = [];

        $buffer = new ActivityImportGenerationBatchBuffer(
            batchSize: 10,
            flushBatch: static function (
                ActivityImportGenerationId $generationId,
                ActivityId $activityId,
                array $items,
            ) use (&$batches): void {
                $batches[] = [$generationId, $activityId, $items];
            },
        );

        $buffer->append($firstGeneration, $firstActivity, $first);
        $buffer->append($firstGeneration, $secondActivity, $second);
        $buffer->append($secondGeneration, $firstActivity, $third);
        $buffer->flush();

        self::assertCount(3, $batches);
        self::assertSame([$first], $batches[0][2]);
        self::assertSame([$second], $batches[1][2]);
        self::assertSame([$third], $batches[2][2]);
        self::assertSame(0, $buffer->pendingCount());
    }

    public function testRetainsFailedBatchUntilDiscard(): void
    {
        $buffer = new ActivityImportGenerationBatchBuffer(
            batchSize: 2,
            flushBatch: static function (): void {
                throw new \RuntimeException('Batch failed.');
            },
        );

        $generationId = ActivityImportGenerationId::generate();
        $activityId = ActivityId::generate();
        $buffer->append($generationId, $activityId, new \stdClass());

        try {
            $buffer->append($generationId, $activityId, new \stdClass());
            self::fail('Expected batch failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Batch failed.', $exception->getMessage());
        }

        self::assertSame(2, $buffer->pendingCount());
        $buffer->discard();
        self::assertSame(0, $buffer->pendingCount());
    }
}
