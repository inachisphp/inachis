<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Util;

use App\Util\RandomColorPicker;
use PHPUnit\Framework\TestCase;

class RandomColorPickerTest extends TestCase
{
    protected RandomColorPicker $colorPicker;

    public function setUp(): void
    {
        $this->colorPicker  = new RandomColorPicker();
        parent::setUp();
    }

    public function testGenerate(): void
    {
        $result = $this->colorPicker->generate();
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testGetAll(): void
    {
        $result = $this->colorPicker->getAll();
        $this->assertIsArray($result);
        $this->assertEquals(['#099bdd', '#f90', '#090', '#dd0909', '#8409dd', '#dd8709'], $result);
    }

    public function testIsValid(): void
    {
        $this->assertTrue($this->colorPicker->isValid('#099bdd'));
        $this->assertFalse($this->colorPicker->isValid('099bdd'));
    }
}
