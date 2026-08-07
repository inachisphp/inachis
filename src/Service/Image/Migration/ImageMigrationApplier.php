<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Media\Image;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ImageMigrationApplier
{
    private const BATCH_SIZE = 50;
    private const MAX_DIMENSION = 1024;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private ImageProcessor $imageProcessor,
        private MarkdownImageRewriter $markdownRewriter,
    ) {
    }

    /**
     * Validate plan freshness against current database state.
     *
     * @param array<string, mixed> $plan
     */
    public function isPlanStale(array $plan): bool
    {
        $meta = $plan['metadata'] ?? [];
        if (empty($meta)) {
            return false;
        }

        $currentImageCount = count($this->imageRepository->findAll());
        $currentPageCount = count($this->pageRepository->findAll());
        $currentSeriesCount = count($this->seriesRepository->findAll());

        return $currentImageCount !== ($meta['imageCount'] ?? -1)
            || $currentPageCount !== ($meta['pageCount'] ?? -1)
            || $currentSeriesCount !== ($meta['seriesCount'] ?? -1);
    }

    /**
     * Apply migration plan.
     *
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $checkpoint
     *
     * @return array<string, mixed>
     */
    public function applyPlan(
        array $plan,
        array $checkpoint,
        string $imageDir,
        string $backupDir,
        OutputInterface $output,
        bool $noWebp,
        bool $noDedup,
        bool $noResize,
        callable $saveCheckpointCallback,
    ): array {
        $errors = [];

        $images = $plan['images'] ?? [];
        $duplicates = $plan['duplicates'] ?? [];
        $contentReplacements = $plan['contentReplacements'] ?? [];

        // 1. Create Physical Backups with SHA-256 Manifest
        $output->writeln('<info>Backing up original image files to var/image-migration/backups/...</info>');
        $backupManifest = [];

        foreach ($images as $img) {
            $srcFile = $imageDir.$img['oldFilename'];
            $bakFile = $backupDir.$img['oldFilename'];

            if (file_exists($srcFile)) {
                if (!file_exists($bakFile)) {
                    copy($srcFile, $bakFile);
                }
                $backupManifest[$img['oldFilename']] = [
                    'sha256' => hash_file('sha256', $bakFile) ?: '',
                    'size' => filesize($bakFile) ?: 0,
                    'timestamp' => date('c'),
                ];
            }
        }
        file_put_contents($backupDir.'backup_manifest.json', (string) json_encode($backupManifest, JSON_PRETTY_PRINT));

        // Build deduplication maps
        $dedupFilenameMap = [];
        $duplicateIdMap = [];
        if (!$noDedup && !empty($duplicates)) {
            $imagePlanById = [];
            foreach ($images as $img) {
                $imagePlanById[$img['id']] = $img;
            }
            foreach ($duplicates as $dup) {
                $duplicateIdMap[$dup['duplicateId']] = $dup['canonicalId'];
                $canonicalPlan = $imagePlanById[$dup['canonicalId']] ?? null;
                if (null !== $canonicalPlan) {
                    $dedupFilenameMap[$dup['duplicateFilename']] = $canonicalPlan['newFilename'];
                }
            }
        }

        // 2. Process Images
        $output->writeln('<info>Processing images...</info>');
        $imageProgress = new ProgressBar($output, count($images));
        $imageProgress->setFormat("%label%\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%eta:-6s% %memory:6s%");
        $imageProgress->setMessage('Processing Images', 'label');
        $imageProgress->setRedrawFrequency(10);
        $imageProgress->start();
        $imageProgress->setProgress($checkpoint['imageIndex']);

        $totalOriginalBytes = 0;
        $totalFinalBytes = 0;
        $renamedCount = 0;
        $resizedCount = 0;
        $webpCount = 0;

        for ($i = $checkpoint['imageIndex']; $i < count($images); ++$i) {
            $imgPlan = &$images[$i];
            $imageId = $imgPlan['id'];

            if (in_array($imageId, $checkpoint['completedImageIds'], true)) {
                $imageProgress->advance();
                continue;
            }

            $oldFilename = $imgPlan['oldFilename'];
            $targetFilename = $imgPlan['newFilename'];
            $oldPath = $imageDir.$oldFilename;
            $newPath = $imageDir.$targetFilename;

            $origSize = $imgPlan['oldFilesize'];
            $totalOriginalBytes += $origSize;

            if ($imgPlan['isDuplicate'] && !$noDedup) {
                $checkpoint['completedImageIds'][] = $imageId;
                $checkpoint['imageIndex'] = $i + 1;
                $saveCheckpointCallback($checkpoint);
                $imageProgress->advance();
                continue;
            }

            $this->entityManager->beginTransaction();
            try {
                /** @var Image|null $imageEntity */
                $imageEntity = $this->imageRepository->find($imageId);
                if (null === $imageEntity) {
                    $errors[] = sprintf('Image entity %s not found in database.', $imageId);
                    $this->entityManager->rollback();
                    $checkpoint['completedImageIds'][] = $imageId;
                    $checkpoint['imageIndex'] = $i + 1;
                    $saveCheckpointCallback($checkpoint);
                    $imageProgress->advance();
                    continue;
                }

                if (file_exists($oldPath)) {
                    $processedPath = $oldPath;

                    // Resize if needed
                    if ($imgPlan['needsResize'] && !$noResize) {
                        $resizedPath = tempnam(sys_get_temp_dir(), 'img_rsz_') ?: ($newPath.'.tmp');
                        try {
                            if ($this->imageProcessor->resizeImage($oldPath, $resizedPath, self::MAX_DIMENSION)) {
                                $processedPath = $resizedPath;
                                ++$resizedCount;
                            }
                        } catch (\Throwable $e) {
                            if (file_exists($resizedPath) && $resizedPath !== $oldPath) {
                                @unlink($resizedPath);
                            }
                            throw $e;
                        }
                    }

                    // WebP Conversion
                    if ($imgPlan['convertToWebp'] && !$noWebp) {
                        $webpCandidatePath = preg_replace('/\.\w+$/', '.webp', $newPath) ?: ($newPath.'.webp');
                        $webpTempPath = tempnam(sys_get_temp_dir(), 'img_webp_') ?: ($webpCandidatePath.'.tmp');

                        try {
                            if ($this->imageProcessor->convertToWebp($processedPath, $webpTempPath)) {
                                $webpSize = filesize($webpTempPath) ?: 0;
                                $procSize = filesize($processedPath) ?: 0;

                                if ($webpSize < $procSize) {
                                    $targetFilename = basename($webpCandidatePath);
                                    $targetPath = $webpCandidatePath;
                                    if ($processedPath !== $oldPath && file_exists($processedPath)) {
                                        @unlink($processedPath);
                                    }
                                    $processedPath = $webpTempPath;
                                    ++$webpCount;
                                    $imgPlan['newFilename'] = $targetFilename;
                                    $contentReplacements[$oldFilename] = $targetFilename;
                                } else {
                                    @unlink($webpTempPath);
                                    $targetPath = $newPath;
                                }
                            } else {
                                $targetPath = $newPath;
                            }
                        } catch (\Throwable $e) {
                            if (file_exists($webpTempPath)) {
                                @unlink($webpTempPath);
                            }
                            throw $e;
                        }
                    } else {
                        $targetPath = $newPath;
                    }

                    // Move to target path safely
                    if ($processedPath !== $oldPath) {
                        if (file_exists($processedPath)) {
                            rename($processedPath, $targetPath);
                        }
                        if ($oldPath !== $targetPath && file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    } elseif ($oldPath !== $targetPath) {
                        rename($oldPath, $targetPath);
                    }

                    ++$renamedCount;
                    $finalSize = file_exists($targetPath) ? (filesize($targetPath) ?: 0) : 0;
                    $totalFinalBytes += $finalSize;
                    $finalChecksum = file_exists($targetPath) ? (hash_file('sha256', $targetPath) ?: '') : '';
                    $finalDimensions = file_exists($targetPath) ? (getimagesize($targetPath) ?: [0, 0]) : [0, 0];

                    $imageEntity->setFilename($targetFilename);
                    $imageEntity->setFilesize($finalSize);
                    $imageEntity->setChecksum($finalChecksum);
                    $imageEntity->setDimensionX($finalDimensions[0] ?? 0);
                    $imageEntity->setDimensionY($finalDimensions[1] ?? 0);

                    $mimeType = file_exists($targetPath) ? $this->imageProcessor->detectMimeType($targetPath) : 'image/jpeg';
                    if ($imageEntity->isValidFiletype($mimeType)) {
                        $imageEntity->setFiletype($mimeType);
                    }
                } else {
                    $errors[] = sprintf('File %s for image entity %s not found on disk.', $oldPath, $imageId);
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

            $checkpoint['completedImageIds'][] = $imageId;
            $checkpoint['imageIndex'] = $i + 1;
            $saveCheckpointCallback($checkpoint);

            $imageProgress->advance();
        }

        $imageProgress->finish();
        $output->writeln('');

        // 3. Update Pages safely by fetching IDs upfront
        $pageIds = array_map(fn ($p) => (string) $p->getId(), $this->pageRepository->findBy([], ['id' => 'ASC']));
        $output->writeln('<info>Updating page content and feature images...</info>');
        $pageProgress = new ProgressBar($output, count($pageIds));
        $pageProgress->setFormat("%label%\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%eta:-6s% %memory:6s%");
        $pageProgress->setMessage('Updating Pages', 'label');
        $pageProgress->setRedrawFrequency(10);
        $pageProgress->start();
        $pageProgress->setProgress($checkpoint['pageIndex']);

        for ($i = $checkpoint['pageIndex']; $i < count($pageIds); ++$i) {
            $pageId = $pageIds[$i];

            if (in_array($pageId, $checkpoint['completedPageIds'], true)) {
                $pageProgress->advance();
                continue;
            }

            $this->entityManager->beginTransaction();
            try {
                /** @var Page|null $page */
                $page = $this->pageRepository->find($pageId);
                if (null !== $page) {
                    if (null !== $page->getFeatureImage() && null !== $page->getFeatureImage()->getId()) {
                        $featId = (string) $page->getFeatureImage()->getId();
                        if (isset($duplicateIdMap[$featId])) {
                            $canonicalEntity = $this->imageRepository->find($duplicateIdMap[$featId]);
                            if (null !== $canonicalEntity) {
                                $page->setFeatureImage($canonicalEntity);
                            }
                        }
                    }

                    $content = $page->getContent();
                    if (!empty($content)) {
                        $updatedContent = $this->markdownRewriter->rewriteContent($content, $contentReplacements, $dedupFilenameMap);
                        $page->setContent($updatedContent);
                        $page->setImageSize($this->computeTotalImageSize($updatedContent, $imageDir));
                    }

                    $this->entityManager->flush();
                }
                $this->entityManager->commit();
                $this->entityManager->clear();
            } catch (\Throwable $e) {
                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
                throw $e;
            }

            $checkpoint['completedPageIds'][] = $pageId;
            $checkpoint['pageIndex'] = $i + 1;
            $saveCheckpointCallback($checkpoint);

            $pageProgress->advance();
        }

        $pageProgress->finish();
        $output->writeln('');

        // 4. Update Series safely by fetching IDs upfront
        $seriesIds = array_map(fn ($s) => (string) $s->getId(), $this->seriesRepository->findBy([], ['id' => 'ASC']));
        $output->writeln('<info>Updating series description and images...</info>');
        $seriesProgress = new ProgressBar($output, count($seriesIds));
        $seriesProgress->setFormat("%label%\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%eta:-6s% %memory:6s%");
        $seriesProgress->setMessage('Updating Series', 'label');
        $seriesProgress->setRedrawFrequency(10);
        $seriesProgress->start();
        $seriesProgress->setProgress($checkpoint['seriesIndex']);

        for ($i = $checkpoint['seriesIndex']; $i < count($seriesIds); ++$i) {
            $seriesId = $seriesIds[$i];

            if (in_array($seriesId, $checkpoint['completedSeriesIds'], true)) {
                $seriesProgress->advance();
                continue;
            }

            $this->entityManager->beginTransaction();
            try {
                /** @var Series|null $series */
                $series = $this->seriesRepository->find($seriesId);
                if (null !== $series) {
                    if (null !== $series->getImage() && null !== $series->getImage()->getId()) {
                        $sImgId = (string) $series->getImage()->getId();
                        if (isset($duplicateIdMap[$sImgId])) {
                            $canonicalEntity = $this->imageRepository->find($duplicateIdMap[$sImgId]);
                            if (null !== $canonicalEntity) {
                                $series->setImage($canonicalEntity);
                            }
                        }
                    }

                    $desc = $series->getDescription();
                    if (!empty($desc)) {
                        $updatedDesc = $this->markdownRewriter->rewriteContent($desc, $contentReplacements, $dedupFilenameMap);
                        $series->setDescription($updatedDesc);
                    }

                    $this->entityManager->flush();
                }
                $this->entityManager->commit();
                $this->entityManager->clear();
            } catch (\Throwable $e) {
                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
                throw $e;
            }

            $checkpoint['completedSeriesIds'][] = $seriesId;
            $checkpoint['seriesIndex'] = $i + 1;
            $saveCheckpointCallback($checkpoint);

            $seriesProgress->advance();
        }

        $seriesProgress->finish();
        $output->writeln('');

        // 5. Clean up duplicate entities and physical files
        if (!$noDedup && !empty($duplicates)) {
            $this->entityManager->beginTransaction();
            try {
                foreach ($duplicates as $dup) {
                    $dupEntity = $this->imageRepository->find($dup['duplicateId']);
                    if (null !== $dupEntity) {
                        $this->entityManager->remove($dupEntity);
                    }
                    $dupPath = $imageDir.$dup['duplicateFilename'];
                    if (file_exists($dupPath)) {
                        @unlink($dupPath);
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

        return [
            'renamedCount' => $renamedCount,
            'resizedCount' => $resizedCount,
            'webpCount' => $webpCount,
            'totalOriginalBytes' => $totalOriginalBytes,
            'totalFinalBytes' => $totalFinalBytes,
            'errors' => $errors,
        ];
    }

    private function computeTotalImageSize(?string $content, string $imageDir): int
    {
        $files = $this->markdownRewriter->extractImageReferences($content);
        $total = 0;

        foreach ($files as $file) {
            $path = $imageDir.$file;
            if (file_exists($path)) {
                $total += (filesize($path) ?: 0);
            }
        }

        return $total;
    }
}
