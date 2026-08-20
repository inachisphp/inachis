<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form\Extension;

use Inachis\Form\Extension\TogglePasswordTypeExtension;
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
