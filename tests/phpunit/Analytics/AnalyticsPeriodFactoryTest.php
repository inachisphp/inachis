<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Analytics;

use Inachis\Analytics\AnalyticsPeriodFactory;
use Inachis\Exception\InvalidAnalyticsPeriodException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AnalyticsPeriodFactoryTest extends TestCase
{
    public function testFromRequestToday(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'today']),
        );

        $this->assertEquals($today, $period->from);
        $this->assertEquals($today, $period->to);
        $this->assertSame('today', $period->range);
        $this->assertSame('Today', $period->label);
    }

    public function testFromRequestYesterday(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'yesterday']),
        );

        $expected = $today->modify('-1 day');

        $this->assertEquals($expected, $period->from);
        $this->assertEquals($expected, $period->to);
        $this->assertSame('yesterday', $period->range);
        $this->assertSame('Yesterday', $period->label);
    }

    public function testFromRequestSevenDays(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => '7d']),
        );

        $this->assertEquals($today->modify('-6 days'), $period->from);
        $this->assertEquals($today, $period->to);
        $this->assertSame('7d', $period->range);
        $this->assertSame('Last 7 Days', $period->label);
    }

    public function testFromRequestThirtyDays(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => '30d']),
        );

        $this->assertEquals($today->modify('-29 days'), $period->from);
        $this->assertEquals($today, $period->to);
        $this->assertSame('30d', $period->range);
        $this->assertSame('Last 30 Days', $period->label);
    }

    public function testFromRequestNinetyDays(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => '90d']),
        );

        $this->assertEquals($today->modify('-89 days'), $period->from);
        $this->assertEquals($today, $period->to);
        $this->assertSame('90d', $period->range);
        $this->assertSame('Last 90 Days', $period->label);
    }

    public function testFromRequestThisMonth(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'this-month']),
        );

        $this->assertEquals(
            $today->modify('first day of this month'),
            $period->from,
        );
        $this->assertEquals($today, $period->to);
        $this->assertSame('this-month', $period->range);
        $this->assertSame('This Month', $period->label);
    }

    public function testFromRequestLastMonth(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'last-month']),
        );

        $this->assertEquals(
            $today->modify('first day of last month'),
            $period->from,
        );
        $this->assertEquals(
            $today->modify('last day of last month'),
            $period->to,
        );
        $this->assertSame('last-month', $period->range);
        $this->assertSame('Last Month', $period->label);
    }

    public function testFromRequestThisYear(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'this-year']),
        );

        $this->assertEquals(
            $today->setDate((int) $today->format('Y'), 1, 1),
            $period->from,
        );
        $this->assertEquals($today, $period->to);
        $this->assertSame('this-year', $period->range);
        $this->assertSame('This Year', $period->label);
    }

    public function testFallbackWhenRangeIsUnknown(): void
    {
        $today = new \DateTimeImmutable('today');

        $period = AnalyticsPeriodFactory::fromRequest(
            new Request(['range' => 'invalid']),
        );

        $this->assertEquals($today->modify('-29 days'), $period->from);
        $this->assertEquals($today, $period->to);
        $this->assertSame('30d', $period->range);
        $this->assertSame('Last 30 Days', $period->label);
    }

    public function testCustomRange(): void
    {
        $today = new \DateTimeImmutable('today');
        $lastMonth = $today->modify('-1 month');
        $period = AnalyticsPeriodFactory::fromRequest(
            new Request([
                'range' => 'custom',
                'from' => $lastMonth->format('Y-m-d'),
                'to' => $today->modify('+1 month')->format('Y-m-d'),
            ]),
        );

        $this->assertEquals(
            $lastMonth,
            $period->from,
        );
        $this->assertEquals(
            $today,
            $period->to,
        );
        $this->assertSame('custom', $period->range);
        $this->assertSame($lastMonth->format('j M Y').' – '.$today->format('j M Y'), $period->label);
    }

    public function testCustomDatesAreSwappedWhenReversed(): void
    {
        $period = AnalyticsPeriodFactory::fromRequest(
            new Request([
                'range' => 'custom',
                'from' => '2025-02-01',
                'to' => '2025-01-01',
            ]),
        );

        $this->assertEquals(
            new \DateTimeImmutable('2025-01-01'),
            $period->from,
        );
        $this->assertEquals(
            new \DateTimeImmutable('2025-02-01'),
            $period->to,
        );
    }

    public function testMissingCustomDatesThrowsException(): void
    {
        $this->expectException(InvalidAnalyticsPeriodException::class);

        AnalyticsPeriodFactory::fromRequest(
            new Request([
                'range' => 'custom',
            ]),
        );
    }

    public function testInvalidCustomDateThrowsException(): void
    {
        $this->expectException(InvalidAnalyticsPeriodException::class);

        AnalyticsPeriodFactory::fromRequest(
            new Request([
                'range' => 'custom',
                'from' => 'invalid',
                'to' => '2025-01-01',
            ]),
        );
    }

    public function testCustomRangeLongerThanOneYearThrowsException(): void
    {
        $this->expectException(InvalidAnalyticsPeriodException::class);

        AnalyticsPeriodFactory::fromRequest(
            new Request([
                'range' => 'custom',
                'from' => '2024-01-01',
                'to' => '2025-12-31',
            ]),
        );
    }
}
