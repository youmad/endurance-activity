<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;

final class ActivityDeviceIdTest extends TestCase
{
    public function testCanGenerateActivityDeviceId(): void
    {
        $id = ActivityDeviceId::generate();

        self::assertNotSame(
            '',
            $id->toString(),
        );
    }

    public function testCanCreateActivityDeviceIdFromString(): void
    {
        $value = '01890f3e-7b6e-7cc0-98c4-dc0c0c07398f';

        $id = ActivityDeviceId::fromString($value);

        self::assertSame(
            $value,
            $id->toString(),
        );
    }

    public function testActivityDeviceIdsCanBeEqual(): void
    {
        $value = '01890f3e-7b6e-7cc0-98c4-dc0c0c07398f';

        $first = ActivityDeviceId::fromString($value);
        $second = ActivityDeviceId::fromString($value);

        self::assertTrue(
            $first->equals($second),
        );
    }

    public function testDifferentActivityDeviceIdsAreNotEqual(): void
    {
        $first = ActivityDeviceId::fromString(
            '01890f3e-7b6e-7cc0-98c4-dc0c0c07398f',
        );

        $second = ActivityDeviceId::fromString(
            '01890f3e-7b6e-7cc0-98c4-dc0c0c073990',
        );

        self::assertFalse(
            $first->equals($second),
        );
    }
}
