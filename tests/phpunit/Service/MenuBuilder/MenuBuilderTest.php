<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\MenuBuilder;

use Inachis\Entity\System\MenuItem;
use Inachis\Service\MenuBuilder\MenuBuilder;
use Inachis\Service\MenuBuilder\MenuProviderInterface;
use Inachis\Service\Plugin\PluginInstallerInterface;
use Inachis\Service\Plugin\PluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MenuBuilder::class)]
class MenuBuilderTest extends TestCase
{
    /**
     * Create a PluginManager pre-configured with a set of enabled provider class names.
     *
     * Each provider must implement both {@see MenuProviderInterface} and
     * {@see PluginInstallerInterface} so that PluginManager registers them by their
     * runtime class name as "enabled". The same instance is then passed as a
     * menu provider to {@see MenuBuilder}.
     *
     * @param array<MenuProviderInterface&PluginInstallerInterface> $enabledProviders
     * @return PluginManager
     */
    private function pluginManagerFor(array $enabledProviders): PluginManager
    {
        return new PluginManager($enabledProviders);
    }

    #[Test]
    public function buildReturnsEmptyArrayWhenNoProvidersRegistered(): void
    {
        $builder = new MenuBuilder($this->pluginManagerFor([]), []);

        self::assertSame([], $builder->build());
    }

    #[Test]
    public function buildExcludesItemsFromUnregisteredProviders(): void
    {
        // Provider is not registered in PluginManager, so isEnabled() returns false
        $provider = new class implements MenuProviderInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Secret', '/secret', 0)];
            }
        };

        $builder = new MenuBuilder($this->pluginManagerFor([]), [$provider]);

        self::assertSame([], $builder->build());
    }

    #[Test]
    public function buildIncludesItemsFromEnabledProvider(): void
    {
        $provider = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Dashboard', '/dashboard', 0)];
            }

            public function install(): void {}
        };

        $builder = new MenuBuilder($this->pluginManagerFor([$provider]), [$provider]);
        $result = $builder->build();

        self::assertCount(1, $result);
        self::assertSame('Dashboard', $result[0]['label']);
        self::assertSame('/dashboard', $result[0]['url']);
        self::assertSame(0, $result[0]['priority']);
    }

    #[Test]
    public function buildMergesItemsFromMultipleEnabledProviders(): void
    {
        $providerA = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Home', '/', 1)];
            }

            public function install(): void {}
        };

        $providerB = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('About', '/about', 2)];
            }

            public function install(): void {}
        };

        $builder = new MenuBuilder(
            $this->pluginManagerFor([$providerA, $providerB]),
            [$providerA, $providerB]
        );

        self::assertCount(2, $builder->build());
    }

    #[Test]
    public function buildSortsByPriorityAscending(): void
    {
        $provider = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [
                    new MenuItem('Low Priority', '/low', 99),
                    new MenuItem('High Priority', '/high', 1),
                    new MenuItem('Medium Priority', '/medium', 50),
                ];
            }

            public function install(): void {}
        };

        $builder = new MenuBuilder($this->pluginManagerFor([$provider]), [$provider]);
        $result = $builder->build();

        self::assertSame(1, $result[0]['priority']);
        self::assertSame(50, $result[1]['priority']);
        self::assertSame(99, $result[2]['priority']);
    }

    #[Test]
    public function buildReturnsMappedArrayShape(): void
    {
        $provider = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Contact', '/contact', 5)];
            }

            public function install(): void {}
        };

        $builder = new MenuBuilder($this->pluginManagerFor([$provider]), [$provider]);
        $result = $builder->build();

        self::assertArrayHasKey('label', $result[0]);
        self::assertArrayHasKey('url', $result[0]);
        self::assertArrayHasKey('priority', $result[0]);
        self::assertCount(3, $result[0], 'Each item should contain exactly 3 keys');
    }

    #[Test]
    public function buildOnlyIncludesItemsFromEnabledProvidersWhenMixed(): void
    {
        $enabledProvider = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Enabled', '/enabled', 0)];
            }

            public function install(): void {}
        };

        // This provider is NOT registered in PluginManager, so it will be skipped
        $disabledProvider = new class implements MenuProviderInterface {
            public function getMenuItems(): array
            {
                return [new MenuItem('Disabled', '/disabled', 0)];
            }
        };

        $builder = new MenuBuilder(
            $this->pluginManagerFor([$enabledProvider]),
            [$enabledProvider, $disabledProvider]
        );
        $result = $builder->build();

        self::assertCount(1, $result);
        self::assertSame('Enabled', $result[0]['label']);
    }

    #[Test]
    public function buildReturnsSequentiallyIndexedArray(): void
    {
        $provider = new class implements MenuProviderInterface, PluginInstallerInterface {
            public function getMenuItems(): array
            {
                return [
                    new MenuItem('A', '/a', 5),
                    new MenuItem('B', '/b', 1),
                ];
            }

            public function install(): void {}
        };

        $builder = new MenuBuilder($this->pluginManagerFor([$provider]), [$provider]);
        $result = $builder->build();

        self::assertSame([0, 1], array_keys($result), 'Result keys should be sequential integers after sorting');
    }
}
