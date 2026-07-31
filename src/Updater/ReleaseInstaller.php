<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use Inachis\Updater\Migration\MigrationRunner;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\Verify\ReleaseVerifier;
use RuntimeException;
use Throwable;

final class ReleaseInstaller
{
    public function __construct(
        private ReleaseLocator $locator,
        private ReleaseExtractor $extractor,
        private ReleaseVerifier $verifier,
        private SymlinkManager $symlinkManager,
        private MigrationRunner $migrationRunner,
        private MaintenanceManager $maintenanceManager,
    ) {}

    public function install(
        Manifest $manifest,
        string $archiveFile,
        array $sharedDirectoryMappings = []
    ): ReleaseInstance {
        // 1. Verify archive integrity
        $this->verifier->verify($archiveFile, $manifest->packageSha256);

        // 2. Provision new release path
        $release = $this->locator->create($manifest->version);

        // 3. Extract release
        $this->extractor->extract($archiveFile, $release->path);

        // 4. Link shared assets (/var/www/inachis/shared/...) into target release
        $this->symlinkManager->linkSharedResources($release->path, $sharedDirectoryMappings);

        // 5. Enable Maintenance Mode
        $this->maintenanceManager->enable();

        $targetVersionBeforeMigration = null;

        try {
            // 6. Run Doctrine Migrations
            $targetVersionBeforeMigration = $this->migrationRunner->migrate(
                $release->path
            );

            // 7. Atomically switch /var/www/inachis/current to new release folder
            $this->symlinkManager->switchCurrent(
                $this->locator->currentLink(),
                $release->path
            );

            // 8. Reset OpCache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

        } catch (Throwable $exception) {
            // Attempt Rollback if migrations had already started/executed
            if ($targetVersionBeforeMigration !== null) {
                $this->migrationRunner->rollbackTo(
                    $targetVersionBeforeMigration,
                    $release->path
                );
            }

            // Clean up extracted release folder
            $this->removeDirectory($release->path);

            // Ensure maintenance mode is turned off so system recovers
            $this->maintenanceManager->disable();

            throw new RuntimeException(
                sprintf('Installation failed and was rolled back: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        // 9. Deployment successful -> Disable Maintenance Mode
        $this->maintenanceManager->disable();

        return $release;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) && !is_link($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
