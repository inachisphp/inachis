<?php

namespace App\Tests\phpunit\Util;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    protected $appExtension;

    public function setUp() : void
    {
        $this->appExtension = new AppExtension();

        parent::setUp();
    }

    public function testGetFilters() : void
    {
        $filters = $this->appExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf('Twig\TwigFilter', $filters[0]);
        $this->assertEquals('activeMenu', $filters[0]->getName());
    }

    public function testActiveMenuFilter() : void
    {
        $this->assertEquals('menu__active', $this->appExtension->activeMenuFilter('test', 'test'));
        $this->assertEmpty('', $this->appExtension->activeMenuFilter('test', 'test23'));
    }
}
