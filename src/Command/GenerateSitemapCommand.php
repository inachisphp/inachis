<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Service\SitemapGenerator;
use Symfony\Component\Filesystem\Filesystem;
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
    protected static $defaultName = 'inachis:generate-sitemap';

    private SitemapGenerator $generator;
    private Filesystem $filesystem;
    private string $publicDir;

    private ParameterBagInterface $params;

    public function __construct(SitemapGenerator $generator, ParameterBagInterface $params)
    {
        parent::__construct();
        $this->generator = $generator;
        $this->filesystem = new Filesystem();
        $this->params = $params;
        // $this->publicDir = __DIR__ . '/../../public';
        $this->publicDir = rtrim($params->get('kernel.project_dir'), '/') . '/public';
    }

    protected function configure(): void
    {
        $this->setDescription('Generates an XML sitemap and saves it to public/sitemap.xml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $xmlContent = $this->generator->generate();
            $targetPath = $this->publicDir . '/sitemap.xml';
            $this->filesystem->dumpFile($targetPath, $xmlContent);
            $output->writeln("<info>Sitemap generated at {$targetPath}</info>");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to generate sitemap: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
