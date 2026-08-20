<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Command;

use Inachis\Build\BuildPipeline;
use Inachis\Build\ReleaseBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'inachis:build',
    description: 'Builds an Inachis release.'
)]
final class BuildReleaseCommand extends Command
{
    public function __construct(
        private readonly ReleaseBuilder $builder,
        private readonly BuildPipeline $pipeline,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        try {
            $workspace = $this->builder->build($io);

            $this->pipeline->run(
                $workspace,
                $io
            );

            $io->success('Release workspace created.');
            $io->writeln($workspace->path);

            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}