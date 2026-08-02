<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Analytics\Provider;

use Inachis\Analytics\AnalyticsPeriod;
use PHPUnit\Framework\TestCase;

class AnalyticsPeriodTest extends TestCase
{
	public function testPrevious()
	{
		$from = new \DateTimeImmutable('2023-01-01');
		$to = new \DateTimeImmutable('2023-01-01');
		$period = new AnalyticsPeriod($from, $to, 'day', 'day');

		$previousPeriod = $period->previous();

		$this->assertEquals($from->modify('-1 day'), $previousPeriod->from);
		$this->assertEquals($to->modify('-1 day'), $previousPeriod->to);
	}
}
