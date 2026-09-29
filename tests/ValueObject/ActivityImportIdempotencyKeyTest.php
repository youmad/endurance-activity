<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Exception\InvalidActivityImportIdempotencyKey;
use Youmad\Endurance\Activity\ValueObject\ActivityImportIdempotencyKey;

final class ActivityImportIdempotencyKeyTest extends TestCase
{
    public function testPreservesValidKey(): void
    {
        $key = ActivityImportIdempotencyKey::fromString(
            'object-storage:activities/42.fit:version-7',
        );

        self::assertSame(
            'object-storage:activities/42.fit:version-7',
            $key->toString(),
        );
        self::assertTrue(
            $key->equals(
                ActivityImportIdempotencyKey::fromString(
                    'object-storage:activities/42.fit:version-7',
                ),
            ),
        );
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValue(string $value): void
    {
        $this->expectException(
            InvalidActivityImportIdempotencyKey::class,
        );

        ActivityImportIdempotencyKey::fromString($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
        yield 'leading whitespace' => [' key'];
        yield 'trailing whitespace' => ['key '];
        yield 'too long' => [str_repeat(
            'x',
            ActivityImportIdempotencyKey::MAX_LENGTH + 1,
        )];
    }
}
