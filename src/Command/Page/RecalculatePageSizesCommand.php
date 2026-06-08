<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Page;

use Inachis\Entity\Content\Page;
use Inachis\Repository\Media\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Repository\Content\PageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to recalculate inner image size mapping for loaded pages
 */
#[AsCommand(
    name: 'inachis:pages:recalculate-sizes',
    description: 'Recalculates the imageSize property for all pages based on their active content',
)]
class RecalculatePageSizesCommand extends Command
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager
     * @param ImageRepository $imageRepository
     * @param PageRepository $pageRepository
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the command
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Recalculating page image sizes…</info>');

        /** @var Page[] $pages */
        $pages = $this->pageRepository->findAll();
        
        $count = 0;

        foreach ($pages as $page) {
            $content = $page->getContent() ?? '';
            $totalSize = 0;

            if (preg_match_all('/\/imgs\/([a-zA-Z0-9_\-\.]+)/', $content, $matches)) {
                $filenames = array_unique($matches[1]);
                
                if (!empty($filenames)) {
                    $images = $this->imageRepository->findBy(['filename' => $filenames]);
                    foreach ($images as $image) {
                        $totalSize += $image->getFilesize();
                    }
                }
            }

            if ($page->getFeatureImage() !== null) {
                $totalSize += $page->getFeatureImage()->getFilesize();
            }

            $page->setImageSize($totalSize);
            
            $count++;
            
            if (($count % 50) === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $output->writeln("<info>Completed recalculating image sizes for $count pages.</info>");

        return Command::SUCCESS;
    }
}
