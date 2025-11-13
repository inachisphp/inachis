<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Util;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    protected AppExtension $appExtension;

    public function setUp(): void
    {
        $this->appExtension = new AppExtension();

        parent::setUp();
    }

    public function testGetFilters(): void
    {
        $filters = $this->appExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf('Twig\TwigFilter', $filters[0]);
        $this->assertEquals('activeMenu', $filters[0]->getName());
    }

    public function testActiveMenuFilter(): void
    {
        $this->assertEquals('menu__active', $this->appExtension->activeMenuFilter('test', 'test'));
        $this->assertEmpty('', $this->appExtension->activeMenuFilter('test', 'test23'));
    }

    public function testBytesToMinimumUnit(): void
    {
        $this->assertEquals('10.00 B', $this->appExtension->bytesToMinimumUnit(10));
        $this->assertEquals('10 B', $this->appExtension->bytesToMinimumUnit(10, true));
        $this->assertEquals('1.00 KiB', $this->appExtension->bytesToMinimumUnit(1024));
        $this->assertEquals('3.81 MiB', $this->appExtension->bytesToMinimumUnit(4000000));
        $this->assertEquals('-100.00 B', $this->appExtension->bytesToMinimumUnit(-100));
    }
}
