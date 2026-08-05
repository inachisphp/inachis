<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;

class ImageMigrationRollback
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private MarkdownImageRewriter $markdownRewriter
    ) {}

    /**
     * Execute full file and database rollback.
     *
     * @param array<string, mixed> $plan
     */
    public function rollbackPlan(
        array $plan,
        string $imageDir,
        string $backupDir,
        OutputInterface $output
    ): void {
        $images = $plan['images'] ?? [];
        $contentReplacements = $plan['contentReplacements'] ?? [];
        $entityBackups = $plan['entityBackups'] ?? [];

        $imageEntityBackups = $entityBackups['images'] ?? [];
        $pageEntityBackups = $entityBackups['pages'] ?? [];
        $seriesEntityBackups = $entityBackups['series'] ?? [];

        // 1. Verify Backup Integrity
        $manifestPath = $backupDir . 'backup_manifest.json';
        if (file_exists($manifestPath)) {
            /** @var array<string, array{sha256: string, size: int}> $manifest */
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            foreach ($manifest as $file => $meta) {
                $bakPath = $backupDir . $file;
                if (file_exists($bakPath)) {
                    $chk = hash_file('sha256', $bakPath);
                    if ($chk !== $meta['sha256']) {
                        $output->writeln(sprintf('<error>Backup checksum mismatch for %s</error>', $file));
                    }
                }
            }
        }

        $this->entityManager->beginTransaction();

        try {
            // 2. Restore Physical Binary Files and Re-instantiate Deleted Image Entities
            foreach ($images as $img) {
                $imageId = $img['id'];
                $oldFilename = $img['oldFilename'];
                $newFilename = $img['newFilename'];

                $bakPath = $backupDir . $oldFilename;
                $targetPath = $imageDir . $oldFilename;
                $currentNewPath = $imageDir . $newFilename;

                if (file_exists($currentNewPath) && $currentNewPath !== $targetPath) {
                    @unlink($currentNewPath);
                }

                if (file_exists($bakPath)) {
                    copy($bakPath, $targetPath);
                }

                $imageEntity = $this->imageRepository->find($imageId);
                $backupData = $imageEntityBackups[$imageId] ?? null;

                if ($imageEntity === null && $backupData !== null) {
                    $imageEntity = new Image();
                    $imageEntity->setId(Uuid::fromString($imageId));
                    $imageEntity->setTitle($backupData['title'] ?? 'Image');
                    if (!empty($backupData['description'])) {
                        $imageEntity->setDescription($backupData['description']);
                    }
                    if (!empty($backupData['authorId'])) {
                        $author = $this->entityManager->find(User::class, $backupData['authorId']);
                        if ($author !== null) {
                            $imageEntity->setAuthor($author);
                        }
                    }
                    if (!empty($backupData['createdAt'])) {
                        $imageEntity->setCreatedAt(new DateTimeImmutable($backupData['createdAt']));
                    }
                    if (!empty($backupData['updatedAt'])) {
                        $imageEntity->setUpdatedAt(new DateTimeImmutable($backupData['updatedAt']));
                    }
                    $this->entityManager->persist($imageEntity);
                }

                if ($imageEntity !== null && $backupData !== null) {
                    $imageEntity->setFilename($backupData['filename']);
                    $imageEntity->setFilesize($backupData['filesize']);
                    $imageEntity->setChecksum($backupData['checksum']);
                    $imageEntity->setDimensionX($backupData['dimensionX']);
                    $imageEntity->setDimensionY($backupData['dimensionY']);
                    if (isset($backupData['filetype']) && $imageEntity->isValidFiletype($backupData['filetype'])) {
                        $imageEntity->setFiletype($backupData['filetype']);
                    }
                }
            }

            // 3. Restore Page content and Feature Image relationships
            $reverseReplacements = array_flip($contentReplacements);
            $pages = $this->pageRepository->findAll();

            foreach ($pages as $page) {
                $pageId = (string) $page->getId();
                $backupData = $pageEntityBackups[$pageId] ?? null;

                if ($backupData !== null) {
                    if (!empty($backupData['featureImageId'])) {
                        $origFeatImage = $this->imageRepository->find($backupData['featureImageId']);
                        if ($origFeatImage !== null) {
                            $page->setFeatureImage($origFeatImage);
                        }
                    } else {
                        $page->setFeatureImage(null);
                    }
                }

                $content = $page->getContent();
                if (!empty($content)) {
                    $revertedContent = $this->markdownRewriter->rewriteContent($content, $reverseReplacements);
                    $page->setContent($revertedContent);
                    $page->setImageSize($this->computeTotalImageSize($revertedContent, $imageDir));
                }
            }

            // 4. Restore Series description and Image relationships
            $seriesList = $this->seriesRepository->findAll();
            foreach ($seriesList as $series) {
                $seriesId = (string) $series->getId();
                $backupData = $seriesEntityBackups[$seriesId] ?? null;

                if ($backupData !== null) {
                    if (!empty($backupData['imageId'])) {
                        $origSeriesImage = $this->imageRepository->find($backupData['imageId']);
                        if ($origSeriesImage !== null) {
                            $series->setImage($origSeriesImage);
                        }
                    } else {
                        $series->setImage(null);
                    }
                }

                $desc = $series->getDescription();
                if (!empty($desc)) {
                    $revertedDesc = $this->markdownRewriter->rewriteContent($desc, $reverseReplacements);
                    $series->setDescription($revertedDesc);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            $this->entityManager->clear();
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    private function computeTotalImageSize(?string $content, string $imageDir): int
    {
        $files = $this->markdownRewriter->extractImageReferences($content);
        $total = 0;

        foreach ($files as $file) {
            $path = $imageDir . $file;
            if (file_exists($path)) {
                $total += (filesize($path) ?: 0);
            }
        }

        return $total;
    }
}
