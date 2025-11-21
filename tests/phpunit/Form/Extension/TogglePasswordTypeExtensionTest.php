<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form\Extension;

use App\Form\Extension\TogglePasswordTypeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class TogglePasswordTypeExtensionTest extends TestCase
{
    public function testGetExtendedType(): void
    {
        $result = TogglePasswordTypeExtension::getExtendedTypes();
        $this->assertEquals([PasswordType::class], $result);
    }
}
