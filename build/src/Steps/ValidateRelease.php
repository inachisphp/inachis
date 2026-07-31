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
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 125)]
final class ValidateRelease implements BuildStepInterface
{
    public static function priority(): int
    {
        return 125;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Validating release');

        $requiredFiles = [
            'composer.json',
            'composer.lock',
            'bin/console',
            'public/index.php',
        ];

        foreach ($requiredFiles as $file) {

            $path = $workspace->path
                . DIRECTORY_SEPARATOR
                . $file;

            if (!file_exists($path)) {
                throw new RuntimeException(
                    sprintf(
                        'Required release file missing: %s',
                        $file
                    )
                );
            }
        }

        $this->validateForbiddenFiles(
            $workspace,
            [
                '.env.local',
                '.env.local.php',
                '.git',
                '.gitignore',
                'var/cache',
                'var/log',
            ]
        );

        $io->success(
            'Release validation passed.'
        );

        return $workspace;
    }

    private function validateForbiddenFiles(
        ReleaseWorkspace $workspace,
        array $forbidden,
    ): void {
        foreach ($forbidden as $file) {

            $path = $workspace->path
                . DIRECTORY_SEPARATOR
                . $file;

            if (file_exists($path)) {
                throw new RuntimeException(
                    sprintf(
                        'Forbidden file included in release: %s',
                        $file
                    )
                );
            }
        }
    }
}
