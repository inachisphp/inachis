<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\Console\Output\OutputInterface;

class ImageMigrationVerifier
{
    public function __construct(
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private MarkdownImageRewriter $markdownRewriter,
    ) {
    }

    /**
     * Run full verification pass.
     */
    public function verify(string $imageDir, OutputInterface $output): bool
    {
        $output->writeln('<info>Verifying image repository and entity integrity...</info>');

        $images = $this->imageRepository->findAll();
        $pages = $this->pageRepository->findAll();
        $seriesList = $this->seriesRepository->findAll();

        $failures = [];

        // 1. Verify Images
        $validImages = 0;
        foreach ($images as $img) {
            $filename = $img->getFilename();
            $path = $imageDir.$filename;

            if (!file_exists($path)) {
                $failures[] = sprintf('Image entity %s: missing file %s', $img->getId(), $filename);
                continue;
            }

            $checksum = hash_file('sha256', $path);
            if ($checksum !== $img->getChecksum()) {
                $failures[] = sprintf('Image entity %s (%s): checksum mismatch', $img->getId(), $filename);
            }

            $dims = @getimagesize($path);
            if (false !== $dims) {
                if ($dims[0] !== $img->getDimensionX() || $dims[1] !== $img->getDimensionY()) {
                    $failures[] = sprintf(
                        'Image entity %s (%s): dimension mismatch (DB: %dx%d vs Disk: %dx%d)',
                        $img->getId(),
                        $filename,
                        $img->getDimensionX(),
                        $img->getDimensionY(),
                        $dims[0],
                        $dims[1],
                    );
                }
            }

            ++$validImages;
        }

        // 2. Verify Pages
        $validPages = 0;
        foreach ($pages as $page) {
            $refs = $this->markdownRewriter->extractImageReferences($page->getContent());
            foreach ($refs as $ref) {
                if (!file_exists($imageDir.$ref)) {
                    $failures[] = sprintf('Page %s (%s): broken image reference /imgs/%s', $page->getId(), $page->getTitle(), $ref);
                }
            }
            ++$validPages;
        }

        // 3. Verify Series
        $validSeries = 0;
        foreach ($seriesList as $series) {
            $refs = $this->markdownRewriter->extractImageReferences($series->getDescription());
            foreach ($refs as $ref) {
                if (!file_exists($imageDir.$ref)) {
                    $failures[] = sprintf('Series %s (%s): broken image reference /imgs/%s', $series->getId(), $series->getTitle(), $ref);
                }
            }
            ++$validSeries;
        }

        if (empty($failures)) {
            $output->writeln(sprintf('✓ <comment>%d</comment> images verified', $validImages));
            $output->writeln(sprintf('✓ <comment>%d</comment> pages verified', $validPages));
            $output->writeln(sprintf('✓ <comment>%d</comment> series verified', $validSeries));
            $output->writeln('✓ no broken references');
            $output->writeln('✓ no missing files');
            $output->writeln('✓ checksums valid');

            return true;
        }

        $output->writeln('<error>Verification failed with issues:</error>');
        foreach ($failures as $f) {
            $output->writeln(sprintf('✗ %s', $f));
        }

        return false;
    }
}
