<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Package;

use Inachis\Model\System\Package;
use Inachis\Service\ManifestLoader;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @template T of Package
 */
abstract readonly class AbstractPackageScanner
{
    use ManifestHelpers;

    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        protected string $projectDir,
        protected CacheItemPoolInterface $cache,
        protected LoggerInterface $logger,
        protected ManifestLoader $manifestLoader,
    ) {
    }

    /**
     * Returns every installed package.
     *
     * @return list<T>
     */
    public function getPackages(): array
    {
        $cache = $this->cache->getItem(
            $this->packagesCacheKey(),
        );

        if ($cache->isHit()) {
            $packages = $cache->get();

            if (is_array($packages)) {
                /* @var list<T> */
                return $packages;
            }
        }

        return $this->rescanPackages();
    }

    /**
     * Returns a package by identifier.
     *
     * @return T|null
     */
    public function getPackage(
        string $identifier,
    ): ?Package {
        foreach ($this->getPackages() as $package) {
            if ($package->identifier === $identifier) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Returns details of the last scan.
     *
     * @return array{
     *     lastScannedAt:int|null,
     *     errorCount:int,
     *     errors:list<string>
     * }
     */
    public function getScanStatus(): array
    {
        $cache = $this->cache->getItem(
            $this->statusCacheKey(),
        );

        if ($cache->isHit()) {
            $status = $cache->get();

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
     * Scans package directories and refreshes the cache.
     *
     * @return list<T>
     */
    public function rescanPackages(): array
    {
        $errors = [];
        $packages = $this->scanPackages($errors);

        $packagesCache = $this->cache->getItem(
            $this->packagesCacheKey(),
        );

        $packagesCache->set($packages);
        $this->cache->save($packagesCache);

        $statusCache = $this->cache->getItem(
            $this->statusCacheKey(),
        );

        $statusCache->set([
            'lastScannedAt' => time(),
            'errorCount' => count($errors),
            'errors' => $errors,
        ]);

        $this->cache->save($statusCache);

        return $packages;
    }

    /**
     * Scans all configured package roots.
     *
     * @param list<string> $errors
     *
     * @return list<T>
     */
    protected function scanPackages(
        array &$errors = [],
    ): array {
        /** @var array<string,T> $packages */
        $packages = [];

        foreach ($this->packageRoots() as $root) {
            if (!is_dir($root)) {
                continue;
            }

            foreach (scandir($root) ?: [] as $directory) {
                if ('.' === $directory || '..' === $directory) {
                    continue;
                }

                $package = $this->loadPackage(
                    $root.DIRECTORY_SEPARATOR.$directory,
                    $errors,
                );

                if (null === $package) {
                    continue;
                }

                if (isset($packages[$package->identifier])) {
                    $message = sprintf(
                        'Duplicate package identifier "%s".',
                        $package->identifier,
                    );

                    $this->logger->warning($message);

                    $errors[] = $message;

                    continue;
                }

                $packages[$package->identifier] = $package;
            }
        }

        uasort(
            $packages,
            static fn (
                Package $a,
                Package $b,
            ): int => strcasecmp(
                $a->name,
                $b->name,
            ),
        );

        /* @var list<T> */
        return array_values($packages);
    }

    /**
     * Loads a package from a directory.
     *
     * @param list<string> $errors
     *
     * @return T|null
     */
    protected function loadPackage(
        string $directory,
        array &$errors = [],
    ): ?Package {
        if (!is_dir($directory)) {
            return null;
        }

        $manifestFile = $directory
            .DIRECTORY_SEPARATOR
            .$this->manifestFilename();

        $manifest = $this->manifestLoader->load($manifestFile);

        if (null === $manifest) {
            $errors[] = sprintf(
                'Skipped invalid package at %s',
                $directory,
            );

            return null;
        }

        if (!$this->isValidManifest($manifest, $directory)) {
            $errors[] = sprintf(
                'Invalid manifest in %s',
                $directory,
            );

            return null;
        }

        return $this->createPackage(
            $directory,
            $manifest,
        );
    }

    protected function createBasePackage(
        string $path,
        array $manifest,
    ): array {
        return [
            (string) ($manifest['identifier'] ?? basename($path)),
            $this->getString($manifest, 'name', 'Unnamed Package'),
            $this->getString($manifest, 'version', '1.0.0'),
            $this->getNullableString($manifest, 'author'),
            $this->getNullableString($manifest, 'description'),
            $this->getNullableString($manifest, 'homepage'),
            $this->getNullableString($manifest, 'license'),
            $path,
        ];
    }

    /**
     * Validates the manifest contains the required fields.
     *
     * @param array<string,mixed> $manifest
     */
    protected function isValidManifest(
        array $manifest,
        string $directory,
    ): bool {
        foreach (['identifier', 'name'] as $requiredField) {
            if (
                !isset($manifest[$requiredField])
                || !is_string($manifest[$requiredField])
                || '' === trim($manifest[$requiredField])
            ) {
                $this->logger->warning(sprintf(
                    'Package "%s" is missing required field "%s".',
                    basename($directory),
                    $requiredField,
                ));

                return false;
            }
        }

        return true;
    }

    /**
     * Cache key for installed packages.
     */
    protected function packagesCacheKey(): string
    {
        return sprintf(
            '%s.installed',
            $this->cachePrefix(),
        );
    }

    /**
     * Cache key for scan status.
     */
    protected function statusCacheKey(): string
    {
        return sprintf(
            '%s.scan.status',
            $this->cachePrefix(),
        );
    }

    /**
     * Prefix used for cache keys.
     */
    abstract protected function cachePrefix(): string;

    /**
     * Directories to scan.
     *
     * @return list<string>
     */
    abstract protected function packageRoots(): array;

    /**
     * Manifest filename.
     */
    abstract protected function manifestFilename(): string;

    /**
     * Creates the concrete package.
     *
     * @param array<string,mixed> $manifest
     *
     * @return T
     */
    abstract protected function createPackage(
        string $directory,
        array $manifest,
    ): Package;
}
