<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Plugin;

use Psr\Container\ContainerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class PluginManager
{
    private array $pluginData = [];

    public function __construct(
        #[TaggedIterator('cms.plugin_installer')]
        private iterable $installers
    ) {
        foreach ($installers as $installer) {
            $name = $installer::class;
            $this->pluginData[$name] = [
                'enabled' => true,
                'version' => '1.0.0',
                'installer' => $installer,
            ];
        }
    }

    public function installPlugin(string $name): void
    {
        if (!isset($this->pluginData[$name])) {
            throw new \RuntimeException("Plugin $name not found");
        }

        $plugin = $this->pluginData[$name];
        if (!$plugin['enabled']) {
            $plugin['enabled'] = true;
        }

        $plugin['installer']->install();
    }

    public function isEnabled(string $name): bool
    {
        return $this->pluginData[$name]['enabled'] ?? false;
    }

    public function getInstalledPlugins(): array
    {
        return array_keys($this->pluginData);
    }

    public function getInstaller(string $pluginClass)
    {
        return $this->pluginData[$pluginClass]['installer'] ?? null;
    }

    public function getVersion(string $pluginClass): ?string
    {
        return $this->pluginData[$pluginClass]['version'] ?? null;
    }

    public function setVersion(string $pluginClass, string $version): void
    {
        if (isset($this->pluginData[$pluginClass])) {
            $this->pluginData[$pluginClass]['version'] = $version;
        }
    }

    // @todo return latest version (could read from composer or plugin metadata)
    public function getLatestVersion(string $pluginClass): string
    {
        // For now, return '1.0.0' or read composer-installed version
        return '1.0.0';
    }
}
