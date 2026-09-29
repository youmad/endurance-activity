<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Import\ActivityImportGenerationClaim;
use Youmad\Endurance\Activity\Application\Port\ActivityImportGenerationRepository;
use Youmad\Endurance\Activity\Application\Port\ActivityTransaction;
use Youmad\Endurance\Activity\Application\UseCase\ActivityImportGenerationCoordinator;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportGenerationId;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

final class ActivityImportGenerationCoordinatorTest extends TestCase
{
    public function testUsesShortTransactionsForClaimActivationAndFailure(): void
    {
        $activityId = ActivityId::generate();
        $generationId = ActivityImportGenerationId::generate();
        $key = ActivityImportIdempotencyKey::fromString('fit:42');
        $log = [];

        $repository = new class($generationId, $log) implements ActivityImportGenerationRepository {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(
                private readonly ActivityImportGenerationId $generationId,
                array &$log,
            ) {
                $this->log = &$log;
            }

            public function claim(
                ActivityId $activityId,
                ActivityImportIdempotencyKey $idempotencyKey,
            ): ActivityImportGenerationClaim {
                $this->log[] = 'claim';

                return ActivityImportGenerationClaim::acquired(
                    $this->generationId,
                );
            }

            public function activate(
                ActivityImportGenerationId $generationId,
            ): void {
                $this->log[] = 'activate';
            }

            public function fail(
                ActivityImportGenerationId $generationId,
            ): void {
                $this->log[] = 'fail';
            }
        };

        $transaction = new class($log) implements ActivityTransaction {
            /** @var list<string> */
            public array $log;

            /** @param list<string> $log */
            public function __construct(array &$log)
            {
                $this->log = &$log;
            }

            public function run(\Closure $operation): mixed
            {
                $this->log[] = 'transaction:begin';

                try {
                    return $operation();
                } finally {
                    $this->log[] = 'transaction:end';
                }
            }
        };

        $coordinator = new ActivityImportGenerationCoordinator(
            generations: $repository,
            transaction: $transaction,
        );

        $claim = $coordinator->claim($activityId, $key);
        $coordinator->activate(
            generationId: $generationId,
            persistAggregate: static function () use (&$log): void {
                $log[] = 'save';
            },
        );
        $coordinator->fail($generationId);

        self::assertTrue($claim->isAcquired());
        self::assertSame(
            [
                'transaction:begin',
                'claim',
                'transaction:end',
                'transaction:begin',
                'save',
                'activate',
                'transaction:end',
                'transaction:begin',
                'fail',
                'transaction:end',
            ],
            $log,
        );
    }
}
