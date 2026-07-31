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
use Symfony\Component\Process\Process;

final class ComposerInstaller implements BuildStepInterface
{
    public static function priority(): int
    {
        return 100;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->comment(
            'Installing production Composer dependencies...'
        );

        $arguments = [
            'composer',
            'install',
            '--prefer-dist',
            '--no-interaction',
            '--no-scripts',
        ];

        if ($workspace->definition->composerNoDev) {
            $arguments[] = '--no-dev';
        }

        if ($workspace->definition->composerOptimizeAutoloader) {
            $arguments[] = '--optimize-autoloader';
        }

        $process = new Process($arguments, $workspace->path);
        $process->setEnv([
            ...$_SERVER,
            ...$_ENV,
            'APP_ENV' => 'prod',
            'APP_DEBUG' => '0',
        ]);
        $process->setWorkingDirectory($workspace->path);
        $process->setTimeout(null);

        $process->run(
            static function (
                string $type,
                string $buffer
            ) use ($io): void {
                $io->writeln(rtrim($buffer));
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf(
                    "Composer install failed:\n%s",
                    $process->getErrorOutput()
                )
            );
        }

        return $workspace;
    }
}
