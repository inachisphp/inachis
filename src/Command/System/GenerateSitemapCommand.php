<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\System;

use Inachis\Service\Url\SitemapGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'inachis:generate-sitemap',
    description: 'Generate sitemap.xml in the public directory',
)]
class GenerateSitemapCommand extends Command
{
    /** @var string */
    private string $publicDir;

    public function __construct(
        private SitemapGenerator $generator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
        $this->publicDir = rtrim($projectDir, '/') . '/public';
    }

    /**
     * Configure the command with a description and any necessary arguments or options.
     */
    protected function configure(): void
    {
        $this->setDescription('Generates an XML sitemap and saves it to public/sitemap.xml');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $this->generator->generate();

            $output->writeln(
                sprintf(
                    '<info>Sitemap generated in %s/sitemap.xml</info>',
                    $this->publicDir
                )
            );

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $output->writeln(
                '<error>Failed to generate sitemap: '
                . $e->getMessage()
                . '</error>'
            );

            return Command::FAILURE;
        }
    }
}
