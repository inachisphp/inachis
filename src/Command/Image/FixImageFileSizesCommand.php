<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Image;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Media\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'inachis:images:fix-filesizes',
    description: 'Fixes missing image file sizes by reading from disk'
)]
class FixImageFileSizesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $imageRepository = $this->entityManager->getRepository(Image::class);
        $images = $imageRepository->findAll();

        $basePath = getcwd() . '/public/imgs/';

        $updated = 0;
        $missing = 0;
        $checked = 0;

        $io->title('Fixing Image Filesizes');

        foreach ($images as $image) {
            $checked++;

            // adjust method name if needed (e.g. getFilesize())
            if (method_exists($image, 'getFilesize') && $image->getFilesize() > 0) {
                continue;
            }

            $filename = $image->getFilename();
            $path = $basePath . $filename;

            if (!file_exists($path) || !is_file($path)) {
                $missing++;
                $io->warning("Missing file: {$filename}");
                continue;
            }

            $size = filesize($path);

            if ($size === false) {
                $missing++;
                $io->warning("Could not read size: {$filename}");
                continue;
            }

            // adjust setter name if needed
            $image->setFilesize($size);

            $updated++;

            $io->text("Updated: {$filename} → {$size} bytes");
        }

        $this->entityManager->flush();

        $io->success([
            "Checked: {$checked}",
            "Updated: {$updated}",
            "Missing files: {$missing}",
        ]);

        return Command::SUCCESS;
    }
}