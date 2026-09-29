<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\ValueObject\LapAdjacency;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacency;
use Youmad\Endurance\Activity\ValueObject\SummaryAdjacencyPolicy;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class SummaryAdjacencyPolicyTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool, bool}> */
    public static function boundaries(): iterable
    {
        yield 'equal' => ['10:00:10.000000', '10:00:10.000000', true, true];
        yield 'one microsecond overlap' => ['10:00:10.000001', '10:00:10.000000', false, true];
        yield 'one microsecond gap' => ['10:00:10.000000', '10:00:10.000001', true, true];
        yield 'two second gap' => ['10:00:10.000000', '10:00:12.000000', true, true];
        yield 'three second gap' => ['10:00:10.000000', '10:00:13.000000', true, false];
        yield 'two second overlap' => ['10:00:10.000000', '10:00:08.000000', false, true];
        yield 'three second overlap' => ['10:00:10.000000', '10:00:07.000000', false, false];
        yield 'floor calculated end' => ['10:00:10.999999', '10:00:08.000000', false, true];
        yield 'do not truncate gap' => ['10:00:10.999999', '10:00:13.000000', true, false];
        yield 'fractional next boundary' => ['10:00:10.000000', '10:00:12.999999', true, true];
        yield 'long gap' => ['10:00:10.000000', '11:00:10.000000', true, false];
    }

    #[DataProvider('boundaries')]
    public function testExplicitPoliciesAndLegacyAdapters(
        string $previous,
        string $next,
        bool $nonOverlapping,
        bool $wholeSeconds,
    ): void {
        $previousEnd = Instant::fromDateTimeImmutable(new \DateTimeImmutable('2026-09-27T'.$previous.'Z'));
        $nextStart = Instant::fromDateTimeImmutable(new \DateTimeImmutable('2026-09-27T'.$next.'Z'));

        self::assertSame($nonOverlapping, SummaryAdjacencyPolicy::NonOverlapping->allows($previousEnd, $nextStart));
        self::assertSame($wholeSeconds, SummaryAdjacencyPolicy::AbutWithinTwoWholeSeconds->allows($previousEnd, $nextStart));
        foreach ([TemporalResolution::Microsecond, TemporalResolution::Second] as $resolution) {
            $expected = TemporalResolution::Second === $resolution ? $wholeSeconds : $nonOverlapping;
            self::assertSame($expected, SummaryAdjacency::allows($previousEnd, $nextStart, $resolution));
            self::assertSame($expected, LapAdjacency::allows($previousEnd, $nextStart, $resolution));
        }
        self::assertSame($previous, $previousEnd->toDateTimeImmutable()->format('H:i:s.u'));
        self::assertSame($next, $nextStart->toDateTimeImmutable()->format('H:i:s.u'));
    }
}
