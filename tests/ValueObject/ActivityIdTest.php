<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Foundation\ValueObject\Uuid;

final class ActivityIdTest extends TestCase
{
    public function testGeneratedActivityIdUsesUuidVersionSeven(): void
    {
        $id = ActivityId::generate();

        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $id->toString(),
        );
    }

    public function testGeneratedActivityIdsAreDifferent(): void
    {
        $first = ActivityId::generate();
        $second = ActivityId::generate();

        self::assertFalse($first->equals($second));
    }

    public function testCanBeConvertedToString(): void
    {
        $activityId = ActivityId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            $activityId->toString(),
        );
    }

    public function testCanBeCreatedFromString(): void
    {
        $uuid = Uuid::generate()->toString();

        $activityId = ActivityId::fromString($uuid);

        self::assertSame($uuid, $activityId->toString());
    }
}
