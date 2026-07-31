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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class CommandRunner implements BuildStepInterface
{
    /**
     * Priority set to 40 so it runs AFTER workspace files are copied,
     * but BEFORE ZipBuilder / ManifestGenerator / Cleanup.
     */
    public static function priority(): int
    {
        return 40;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        if (empty($workspace->definition->commands)) {
            return $workspace;
        }
        $io->section('Running Build Commands');

        foreach ($workspace->definition->commands as $command) {
            $io->comment(sprintf(
                'Running "%s" in %s...',
                $command, 
                $workspace->path
            ));

            $process = Process::fromShellCommandline(
                $command,
                $workspace->path,
            );

            $process->setTimeout(300);

            try {
                $process->mustRun(function (
                    string $type,
                    string $buffer
                ) use ($io) {
                    $io->write($buffer);
                });
            } catch (ProcessFailedException $exception) {
                $io->error(sprintf(
                    'Command "%s" failed: %s', 
                    $command, 
                    $exception->getMessage()
                ));
                throw $exception;
            }
        }

        return $workspace;
    }
}
