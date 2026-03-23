<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Plugin;

use Psr\Container\ContainerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Manages plugins
 */
final class PluginManager
{
    /**
     * @var array<string, array{enabled: bool, version: string, installer: PluginInstallerInterface}> $pluginData
     */
    private array $pluginData = [];

    /**
     * Creates a new instance of the PluginManager
     * 
     * @param iterable<PluginInstallerInterface> $installers
     */
    public function __construct(
        #[TaggedIterator('cms.plugin_installer', default: [])]
        private iterable $installers = []
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

    /**
     * Installs a plugin
     * 
     * @param string $name
     * @throws \RuntimeException
     */
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

    /**
     * Checks if a plugin is enabled
     * 
     * @param string $name
     * @return bool
     */
    public function isEnabled(string $name): bool
    {
        return $this->pluginData[$name]['enabled'] ?? false;
    }

    /**
     * Gets all installed plugins
     * 
     * @return array<string>
     */
    public function getInstalledPlugins(): array
    {
        return array_keys($this->pluginData);
    }

    /**
     * Gets the installer for a plugin
     * 
     * @param string $pluginClass
     * @return PluginInstallerInterface|null
     */
    public function getInstaller(string $pluginClass): ?PluginInstallerInterface
    {
        return $this->pluginData[$pluginClass]['installer'] ?? null;
    }

    /**
     * Gets the version of a plugin
     * 
     * @param string $pluginClass
     * @return string|null
     */
    public function getVersion(string $pluginClass): ?string
    {
        return $this->pluginData[$pluginClass]['version'] ?? null;
    }

    /**
     * Sets the version of a plugin
     * 
     * @param string $pluginClass
     * @param string $version
     */
    public function setVersion(string $pluginClass, string $version): void
    {
        if (isset($this->pluginData[$pluginClass])) {
            $this->pluginData[$pluginClass]['version'] = $version;
        }
    }

    /**
     * Gets the latest version of a plugin
     * 
     * @param string $pluginClass
     * @return string
     */
    public function getLatestVersion(string $pluginClass): string
    {
        // @todo return latest version (could read from composer or plugin metadata)
        // For now, return '1.0.0' or read composer-installed version
        return '1.0.0';
    }
}
