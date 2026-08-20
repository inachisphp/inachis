<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit;

use Inachis\InachisBundle;
use PHPUnit\Framework\TestCase;

class InachisBundleTest extends TestCase
{
    public function testGetPath()
    {
        $bundle = new InachisBundle();
        $path = $bundle->getPath();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }
}
