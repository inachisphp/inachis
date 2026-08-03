<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit;

use Inachis\Kernel;
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
