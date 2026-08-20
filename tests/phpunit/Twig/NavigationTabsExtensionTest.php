<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Twig;

use Inachis\Model\NavigationTabDto;
use Inachis\Service\Navigation\NavigationTabService;
use Inachis\Twig\NavigationTabsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class NavigationTabsExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsRegisteredTwigFunctions(): void
    {
        $navigationService = $this->createNavigationTabService();
        $extension = new NavigationTabsExtension($navigationService);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('navigation_tabs', $functions[0]->getName());
    }

    public function testGetTabsDelegatesToNavigationTabService(): void
    {
        $dummyTabs = [
            $this->createNavigationTabDto(),
            $this->createNavigationTabDto(),
        ];

        $navigationService = $this->createNavigationTabService($dummyTabs);
        $extension = new NavigationTabsExtension($navigationService);

        $result = $extension->getTabs();

        $this->assertSame($dummyTabs, $result);
    }

    private function createNavigationTabDto(): NavigationTabDto
    {
        $reflection = new \ReflectionClass(NavigationTabDto::class);

        if (!$reflection->isFinal()) {
            return $this->createStub(NavigationTabDto::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Helper to safely instantiate NavigationTabService whether it is a normal class or declared final.
     */
    private function createNavigationTabService(array $activeTabs = []): NavigationTabService
    {
        $reflection = new \ReflectionClass(NavigationTabService::class);

        if (!$reflection->isFinal()) {
            $service = $this->createStub(NavigationTabService::class);
            $service->method('getActiveTabs')->willReturn($activeTabs);

            return $service;
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}
