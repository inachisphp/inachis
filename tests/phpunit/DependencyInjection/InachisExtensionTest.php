<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\DependencyInjection;

use Inachis\DependencyInjection\InachisExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InachisExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new InachisExtension();
        $extension->load([], $container);
        $container->compile();
        $this->assertTrue(
            $container->hasDefinition('service_container')
        );
    }
}
