<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Steps;

use Inachis\Build\BuildStepInterface;
use Inachis\Build\ReleaseWorkspace;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 0)]
final class CleanupRelease implements BuildStepInterface
{
    public static function priority(): int
    {
        return 0;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Cleaning release');

        $remove = [
            '.env',
            '.env.local',
            '.env.local.php',
            '.DS_Store',
            'build.js',
            'assets',
            'node_modules',
            'package.json',
            'var/cache',
            'var/log',
        ];

        foreach ($remove as $path) {
            $target = $workspace->path
                . DIRECTORY_SEPARATOR
                . $path;

            if (!file_exists($target)) {
                continue;
            }

            $this->remove($target);

            $io->writeln(
                sprintf(
                    '<info>✓</info> Removed %s',
                    $path
                )
            );
        }

        // Remove tests, docs, and git repos inside vendor/
        $vendorDir = $workspace->path . '/vendor';

        $patternsToClean = [
            '/*/*/tests',
            '/*/*/test',
            '/*/*/Tests',
            '/*/*/doc',
            '/*/*/docs',
            '/*/*/*.md',
            '/*/*/phpunit.xml*',
            '/*/*/.git*',
        ];

        foreach ($patternsToClean as $pattern) {
            foreach (glob($vendorDir . $pattern) as $path) {
                if (is_dir($path)) {
                    self::remove($path);
                } elseif (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return $workspace;
    }

    private function remove(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }
}
