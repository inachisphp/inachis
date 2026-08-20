<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\User;

use Inachis\Service\User\ProfileColorPalette;
use PHPUnit\Framework\TestCase;

class ProfileColorPaletteTest extends TestCase
{
    protected ProfileColorPalette $colorPicker;

    public function setUp(): void
    {
        $this->colorPicker = new ProfileColorPalette();
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
