<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Twig;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use Inachis\Twig\MaintenanceExtension;
use PHPUnit\Framework\TestCase;

class MaintenanceExtensionTest extends TestCase
{
    public function testGetGlobalsReturnsTrueWhenMaintenanceIsEnabled(): void
    {
        $maintenanceManager = $this->createMaintenanceManager(true);
        $extension = new MaintenanceExtension($maintenanceManager);

        $globals = $extension->getGlobals();

        $this->assertSame([
            'maintenance_enabled' => true,
        ], $globals);
    }

    public function testGetGlobalsReturnsFalseWhenMaintenanceIsDisabled(): void
    {
        $maintenanceManager = $this->createMaintenanceManager(false);
        $extension = new MaintenanceExtension($maintenanceManager);

        $globals = $extension->getGlobals();

        $this->assertSame([
            'maintenance_enabled' => false,
        ], $globals);
    }

    /**
     * Helper to safely instantiate MaintenanceManager whether it is a normal class or declared final.
     */
    private function createMaintenanceManager(bool $enabled): MaintenanceManager
    {
        $reflection = new \ReflectionClass(MaintenanceManager::class);

        if (!$reflection->isFinal()) {
            $manager = $this->createStub(MaintenanceManager::class);
            $manager->method('isEnabled')->willReturn($enabled);

            return $manager;
        }

        $manager = $reflection->newInstanceWithoutConstructor();

        foreach (['enabled', 'isMaintenanceEnabled', 'maintenance', 'active'] as $propName) {
            if ($reflection->hasProperty($propName)) {
                $prop = $reflection->getProperty($propName);
                $prop->setValue($manager, $enabled);
            }
        }

        return $manager;
    }
}
