<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit;

use App\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    protected $kernel;

    public function setUp(): void
    {
        $this->kernel = new Kernel('test', false);

        parent::setUp();
    }

    public function testGetCacheDir(): void
    {
        $this->assertEquals(
            str_replace('/tests/phpunit', '/var/cache/test', __DIR__),
            $this->kernel->getCacheDir()
        );
    }

    public function testGetLogDir(): void
    {
        $this->assertEquals(
            str_replace('/tests/phpunit', '/var/log', __DIR__),
            $this->kernel->getLogDir()
        );
    }
}
