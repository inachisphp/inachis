<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater;

use RuntimeException;

final class SymlinkManager
{
    /**
     * Atomically switches the 'current' symlink to point to targetPath.
     */
    public function switchCurrent(string $currentLink, string $targetPath): void
    {
        $tempLink = $currentLink . '_tmp_' . uniqid('', true);

        // Create temporary symlink pointing to the new release
        if (!@symlink($targetPath, $tempLink)) {
            throw new RuntimeException(sprintf('Failed to create temporary symlink at "%s".', $tempLink));
        }

        // Atomic swap (rename overwrites existing symlink on POSIX)
        if (!@rename($tempLink, $currentLink)) {
            @unlink($tempLink);
            throw new RuntimeException(sprintf('Failed to switch current symlink to "%s".', $targetPath));
        }
    }

    /**
     * Links shared resources (files or folders) into the extracted release directory.
     *
     * @param string $releasePath Path to extracted release directory
     * @param array<string, string> $sharedMappings Relative path in release => Shared target path in /shared/
     */
    public function linkSharedResources(string $releasePath, array $sharedMappings): void
    {
        foreach ($sharedMappings as $relativeReleasePath => $sharedTargetPath) {
            $fullReleasePath = $releasePath . DIRECTORY_SEPARATOR . ltrim($relativeReleasePath, '/\\');

            // If the shared target doesn't exist yet, create missing directories or empty files safely
            if (!file_exists($sharedTargetPath) && !is_link($sharedTargetPath)) {
                $sharedParent = dirname($sharedTargetPath);
                if (!is_dir($sharedParent)) {
                    mkdir($sharedParent, 0775, true);
                }
            }

            // Clean up default placeholders extracted from the ZIP archive
            if (is_link($fullReleasePath) || is_file($fullReleasePath)) {
                unlink($fullReleasePath);
            } elseif (is_dir($fullReleasePath)) {
                $this->removeDirectory($fullReleasePath);
            }

            // Ensure parent directory exists in release folder before creating symlink
            $parentDir = dirname($fullReleasePath);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0775, true);
            }

            // Create the symlink pointing to the shared folder or file
            if (!@symlink($sharedTargetPath, $fullReleasePath)) {
                throw new RuntimeException(
                    sprintf('Failed to symlink shared resource "%s" -> "%s".', $fullReleasePath, $sharedTargetPath)
                );
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) && !is_link($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
