<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Util;

use Inachis\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AppExtensionTest extends TestCase
{
    protected AppExtension $appExtension;

    public function setUp(): void
    {
        $security = $this->createStub(Security::class);
        $this->appExtension = new AppExtension($security);

        parent::setUp();
    }

    public function testGetFilters(): void
    {
        $filters = $this->appExtension->getFilters();
        $this->assertCount(2, $filters);
        $this->assertInstanceOf('Twig\TwigFilter', $filters[0]);
        $this->assertInstanceOf('Twig\TwigFilter', $filters[1]);
        $this->assertEquals('activeMenu', $filters[0]->getName());
        $this->assertEquals('formatLocalTime', $filters[1]->getName());
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
        $this->assertEquals('0 B', $this->appExtension->bytesToMinimumUnit(-100));
    }
}
