<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Plugin;

use Inachis\Model\System\PluginDto;
use Inachis\Service\ManifestLoader;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PluginScanner
{
    private const CACHE_KEY_PLUGINS = 'plugins.installed';
    private const CACHE_KEY_SCAN_STATUS = 'plugins.scan.status';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private ManifestLoader $manifestLoader,
    ) {
    }

    /**
     * Returns all discovered plugins.
     *
     * @return array<PluginDto>
     */
    public function getPlugins(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_PLUGINS);

        if ($cacheItem->isHit()) {
            $plugins = $cacheItem->get();

            if (is_array($plugins)) {
                return $plugins;
            }
        }

        return $this->rescanPlugins();
    }

    /**
     * Returns a plugin by identifier.
     */
    public function getPlugin(string $identifier): ?PluginDto
    {
        foreach ($this->getPlugins() as $plugin) {
            if ($plugin->identifier === $identifier) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Returns the last scan status.
     *
     * @return array{
     *     lastScannedAt:int|null,
     *     errorCount:int,
     *     errors:string[]
     * }
     */
    public function getScanStatus(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_SCAN_STATUS);

        if ($cacheItem->isHit()) {
            $status = $cacheItem->get();

            if (is_array($status)) {
                return array_merge([
                    'lastScannedAt' => null,
                    'errorCount' => 0,
                    'errors' => [],
                ], $status);
            }
        }

        return [
            'lastScannedAt' => null,
            'errorCount' => 0,
            'errors' => [],
        ];
    }

    /**
     * Rescans all plugin directories.
     *
     * @return array<PluginDto>
     */
    public function rescanPlugins(): array
    {
        $errors = [];

        $plugins = $this->scanPlugins($errors);

        $cacheItem = $this->cache->getItem(self::CACHE_KEY_PLUGINS);
        $cacheItem->set($plugins);
        $this->cache->save($cacheItem);

        $status = $this->cache->getItem(self::CACHE_KEY_SCAN_STATUS);
        $status->set([
            'lastScannedAt' => time(),
            'errorCount' => count($errors),
            'errors' => $errors,
        ]);
        $this->cache->save($status);

        return $plugins;
    }

    /**
     * @param list<string> $errors
     * @return array<PluginDto>
     */
    private function scanPlugins(array &$errors): array
    {
        $plugins = [];
        $seenIdentifiers = [];

        foreach ($this->getPluginRoots() as $pluginRoot) {
            if (!is_dir($pluginRoot)) {
                continue;
            }

            foreach (scandir($pluginRoot) ?: [] as $directory) {
                if ($directory === '.' || $directory === '..') {
                    continue;
                }

                $plugin = $this->loadPlugin(
                    $pluginRoot . DIRECTORY_SEPARATOR . $directory
                );

                if ($plugin === null) {
                    continue;
                }

                if (isset($seenIdentifiers[$plugin->identifier])) {
                    $message = sprintf(
                        'Duplicate plugin identifier "%s" found.',
                        $plugin->identifier
                    );

                    $this->logger->warning($message);
                    $errors[] = $message;

                    continue;
                }

                $seenIdentifiers[$plugin->identifier] = true;
                $plugins[] = $plugin;
            }
        }

        usort(
            $plugins,
            static fn (
                PluginDto $a,
                PluginDto $b
            ): int => strcasecmp($a->name, $b->name)
        );

        return $plugins;
    }

    private function loadPlugin(string $pluginDirectory): ?PluginDto
    {
        if (!is_dir($pluginDirectory)) {
            return null;
        }

        $manifestPath = $pluginDirectory . '/plugin.yaml';

        $manifest = $this->manifestLoader->load($manifestPath);

        if ($manifest === null) {
            return null;
        }

        if (!$this->isValidManifest($manifest, $pluginDirectory)) {
            return null;
        }

        return $this->createPluginDto(
            $pluginDirectory,
            $manifest
        );
    }

    /**
     * @param array<mixed> $manifest
     */
    private function isValidManifest(
        array $manifest,
        string $pluginDirectory
    ): bool {
        foreach (['identifier', 'name'] as $requiredField) {
            if (
                !isset($manifest[$requiredField]) ||
                !is_string($manifest[$requiredField]) ||
                trim($manifest[$requiredField]) === ''
            ) {
                $this->logger->warning(sprintf(
                    'Plugin "%s" is missing required field "%s".',
                    basename($pluginDirectory),
                    $requiredField
                ));

                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $manifest
     */
    private function createPluginDto(
        string $pluginDirectory,
        array $manifest
    ): PluginDto {
        $plugin = new PluginDto();

        $plugin->identifier = (string) $manifest['identifier'];
        $plugin->name = (string) $manifest['name'];
        $plugin->version = (string) ($manifest['version'] ?? '1.0.0');
        $plugin->author = (string) ($manifest['author'] ?? '');
        $plugin->description = (string) ($manifest['description'] ?? '');
        $plugin->homepage = (string) ($manifest['homepage'] ?? '');
        $plugin->license = (string) ($manifest['license'] ?? '');
        $plugin->path = $pluginDirectory;

        $plugin->features = $this->extractStringList(
            $manifest['features'] ?? null
        );

        $plugin->requires = is_array($manifest['requires'] ?? null)
            ? $manifest['requires']
            : [];

        $plugin->suggests = is_array($manifest['suggests'] ?? null)
            ? $manifest['suggests']
            : [];

        return $plugin;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function extractStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                $value,
                static fn (mixed $item): bool => is_string($item)
            )
        );
    }

    /**
     * Returns the directories to search for plugins.
     *
     * @return list<string>
     */
    private function getPluginRoots(): array
    {
        return [
            $this->projectDir . '/plugins',
        ];
    }
}
