<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
