<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportProcessingClaim;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarning;
use Youmad\Endurance\Activity\Application\Import\ActivityImportWarningCode;
use Youmad\Endurance\Activity\Application\Port\ActivityImportRepository;
use Youmad\Endurance\Activity\Application\UseCase\ActivityImportLifecycle;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportProcessingClaimId;

final class ActivityImportLifecycleTest extends TestCase
{
    public function testQueuesAndStartsImport(): void
    {
        $importId = ActivityImportId::generate();
        $activityId = ActivityId::fromString($importId->toString());
        $claim = $this->claim($importId, 1);
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('enqueue')
            ->with($importId, $activityId);
        $repository
            ->expects(self::once())
            ->method('start')
            ->with($importId)
            ->willReturn($claim);

        $lifecycle = new ActivityImportLifecycle($repository);
        $lifecycle->queue($importId, $activityId);

        self::assertSame($claim, $lifecycle->start($importId));
    }

    public function testSkipsUnclaimableRedelivery(): void
    {
        $importId = ActivityImportId::generate();
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('start')
            ->with(self::identicalTo($importId))
            ->willReturn(null);

        self::assertNull(
            (new ActivityImportLifecycle($repository))->start($importId),
        );
    }

    public function testForwardsHeartbeatAndRelease(): void
    {
        $claim = $this->claim(ActivityImportId::generate(), 2);
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('heartbeat')
            ->with(self::identicalTo($claim));
        $repository
            ->expects(self::once())
            ->method('release')
            ->with(self::identicalTo($claim));

        $lifecycle = new ActivityImportLifecycle($repository);
        $lifecycle->heartbeat($claim);
        $lifecycle->release($claim);
    }

    public function testCompletesImportWithStructuredWarnings(): void
    {
        $claim = $this->claim(ActivityImportId::generate(), 1);
        $warning = new ActivityImportWarning(
            code: ActivityImportWarningCode::MissingLapRecovered,
            message: 'Lap summary was recovered.',
        );
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($claim),
                self::identicalTo([$warning]),
            );

        (new ActivityImportLifecycle($repository))->complete(
            $claim,
            [$warning],
        );
    }

    public function testRejectsTooManyWarnings(): void
    {
        $warning = new ActivityImportWarning(
            code: ActivityImportWarningCode::MissingLapRecovered,
            message: 'Lap summary was recovered.',
        );
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository->expects(self::never())->method('complete');

        $this->expectException(\InvalidArgumentException::class);

        (new ActivityImportLifecycle($repository))->complete(
            $this->claim(ActivityImportId::generate(), 1),
            array_fill(0, 65, $warning),
        );
    }

    public function testRejectsUnsafePublicFailure(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ActivityImportLifecycle(
            $this->createStub(ActivityImportRepository::class),
        ))->fail(
            $this->claim(ActivityImportId::generate(), 1),
            'Database connection failed!',
            'Internal credentials and stack trace.',
        );
    }

    public function testFailsQueuedImportAfterRetryExhaustion(): void
    {
        $importId = ActivityImportId::generate();
        $repository = $this->createMock(ActivityImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('failQueued')
            ->with(
                self::identicalTo($importId),
                'processing_failed',
                'The import could not be completed.',
            )
            ->willReturn(true);

        self::assertTrue(
            (new ActivityImportLifecycle($repository))->failQueued(
                $importId,
                'processing_failed',
                'The import could not be completed.',
            ),
        );
    }

    private function claim(
        ActivityImportId $importId,
        int $attemptCount,
    ): ActivityImportProcessingClaim {
        return new ActivityImportProcessingClaim(
            importId: $importId,
            claimId: ActivityImportProcessingClaimId::generate(),
            attemptCount: $attemptCount,
        );
    }
}
