<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Service\SitemapGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
        // $this->publicDir = __DIR__ . '/../../public';
        $this->publicDir = rtrim($params->get('kernel.project_dir'), '/') . '/public';
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
