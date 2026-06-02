<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Image;

use Inachis\Entity\Image;
use Inachis\Repository\PageRepository;
use Inachis\Repository\ImageRepository;
use Inachis\Service\Resource\ImageFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'inachis:images:sync-from-content',
    description: 'Scan Page content for markdown images and ensure Image entities exist'
)]
class SyncImagesFromContentCommand extends Command
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly ImageRepository $imageRepository,
        private readonly EntityManagerInterface $em,
        private readonly ImageFileService $imageFileService,
        private readonly string $imageDirectory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Scan Page content for markdown images and ensure Image entities exist')
            ->addOption(
                'fix',
                null,
                InputOption::VALUE_NONE,
                'Create missing Image entities (otherwise report only)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir($this->imageDirectory)) {
            throw new \RuntimeException(sprintf(
                'Image directory "%s" does not exist or is not a directory.',
                $this->imageDirectory
            ));
        }
        $fix = $input->getOption('fix');

        $pages = $this->pageRepository->createQueryBuilder('p')
            ->select('p.id, p.content, p.title')
            ->getQuery()
            ->toIterable();

        $imageData = []; // filename => ['alt' => string, 'pages' => []]

        // --- PASS 1: Extract all image references ---
        foreach ($pages as $row) {
            $content = $row['content'] ?? '';

            if (!$content) {
                continue;
            }

            preg_match_all('/!\[([^]]*)]\(([^)]+)\)/', $content, $matches);

            $alts  = $matches[1] ?? [];
            $paths = $matches[2] ?? [];

            foreach ($paths as $idx => $path) {
                // Skip external images
                if (str_starts_with($path, 'http')) {
                    continue;
                }

                // Strip query strings/fragments
                $path = strtok($path, '?#');

                // Remove /imgs/ prefix
                $path = preg_replace('#^/?imgs/#', '', $path);

                $filename = basename($path);

                if (!$filename) {
                    continue;
                }

                if (!isset($imageData[$filename])) {
                    $imageData[$filename] = [
                        'alt' => $alts[$idx] ?? '',
                        'pages' => [],
                    ];
                }

                // Prefer first non-empty alt text
                if (empty($imageData[$filename]['alt']) && !empty($alts[$idx])) {
                    $imageData[$filename]['alt'] = $alts[$idx];
                }

                $imageData[$filename]['pages'][] = $row['title'] ?? $row['id'];
            }
        }

        if (empty($imageData)) {
            $output->writeln('<info>No images found in content.</info>');
            return Command::SUCCESS;
        }

        $filenames = array_keys($imageData);

        // --- PASS 2: Fetch existing images ---
        $existingImages = $this->imageRepository->createQueryBuilder('i')
            ->select('i.filename')
            ->where('i.filename IN (:filenames)')
            ->setParameter('filenames', $filenames)
            ->getQuery()
            ->getArrayResult();

        $existingMap = array_flip(array_column($existingImages, 'filename'));

        // --- PASS 3: Determine missing ---
        $missing = array_diff($filenames, array_keys($existingMap));

        if (empty($missing)) {
            $output->writeln('<info>All images already exist as entities.</info>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<comment>%d missing images found:</comment>',
            count($missing)
        ));

        foreach ($missing as $filename) {
            $output->writeln(" - $filename");

            if ($output->isVerbose()) {
                foreach ($imageData[$filename]['pages'] as $pageTitle) {
                    $output->writeln("     used in: $pageTitle");
                }
            }
        }

        // --- REPORT MODE ---
        if (!$fix) {
            $output->writeln('<info>Run with --fix to create missing Image entities.</info>');
            return Command::SUCCESS;
        }

        // --- PASS 4: Create missing images ---
        $batchSize = 25;
        $i = 0;

        foreach ($missing as $filename) {
            $fullPath = rtrim($this->imageDirectory, '/') . '/' . $filename;

            if (!file_exists($fullPath)) {
                $output->writeln("<error>File missing on disk: $fullPath</error>");
                continue;
            }

            $file = new UploadedFile(
                $fullPath,
                $filename,
                null,
                null,
                true
            );

            $image = new Image();
            $image->setFilename($filename);

            // ALT TEXT
            $image->setAltText($imageData[$filename]['alt'] ?? '');

            // FILESIZE (KB)
            $image->setFilesize(filesize($fullPath));

            // MIME TYPE
            $image->setFiletype($file->getMimeType());

            // DIMENSIONS
            $dimensions = $this->imageFileService->getImageDimensions($file);
            if ($dimensions !== false) {
                $image->setDimensionX($dimensions[0]);
                $image->setDimensionY($dimensions[1]);
            }

            // CHECKSUM
            $checksum = $this->imageFileService->createChecksum($file);
            $image->setChecksum($checksum);

            // TITLE fallback
            $image->setTitle($imageData[$filename]['alt'] ?? 'Unnamed Image');

            $this->em->persist($image);
            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }

            $i++;
        }

        $this->em->flush();

        $output->writeln('<info>Missing images have been created.</info>');

        return Command::SUCCESS;
    }
}