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
        private MarkdownImageRewriter $markdownRewriter,
    ) {
    }

    /**
     * Execute full file and database rollback.
     *
     * @param array{
     *     images?: list<array{id: string, oldFilename: string, newFilename: string}>,
     *     contentReplacements?: array<string, string>,
     *     entityBackups?: array{
     *         images?: array<string, array{
     *             title?: string,
     *             description?: string|null,
     *             authorId?: string|null,
     *             createdAt?: string|null,
     *             updatedAt?: string|null,
     *             filename: string,
     *             filesize: int,
     *             checksum: string,
     *             dimensionX: int,
     *             dimensionY: int,
     *             filetype?: string
     *         }>,
     *         pages?: array<string, array{featureImageId?: string|null}>,
     *         series?: array<string, array{imageId?: string|null}>
     *     }
     * } $plan
     */
    public function rollbackPlan(
        array $plan,
        string $imageDir,
        string $backupDir,
        OutputInterface $output,
    ): void {
        $images = $plan['images'] ?? [];
        /** @var array<string, string> $contentReplacements */
        $contentReplacements = $plan['contentReplacements'] ?? [];
        $entityBackups = $plan['entityBackups'] ?? [];

        $imageEntityBackups = $entityBackups['images'] ?? [];
        $pageEntityBackups = $entityBackups['pages'] ?? [];
        $seriesEntityBackups = $entityBackups['series'] ?? [];

        // 1. Verify Backup Integrity
        $manifestPath = $backupDir.'backup_manifest.json';
        if (file_exists($manifestPath)) {
            /** @var array<string, array{sha256: string, size: int}> $manifest */
            $manifest = json_decode((string) file_get_contents($manifestPath), true) ?? [];
            foreach ($manifest as $file => $meta) {
                $bakPath = $backupDir.$file;
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
                $imageId = (string) $img['id'];
                $oldFilename = (string) $img['oldFilename'];
                $newFilename = (string) $img['newFilename'];

                $bakPath = $backupDir.$oldFilename;
                $targetPath = $imageDir.$oldFilename;
                $currentNewPath = $imageDir.$newFilename;

                if (file_exists($currentNewPath) && $currentNewPath !== $targetPath) {
                    @unlink($currentNewPath);
                }

                if (file_exists($bakPath)) {
                    copy($bakPath, $targetPath);
                }

                $imageEntity = $this->imageRepository->find($imageId);
                $backupData = $imageEntityBackups[$imageId] ?? null;

                if (null === $imageEntity && null !== $backupData) {
                    $imageEntity = new Image();
                    $imageEntity->setId(Uuid::fromString($imageId));
                    $imageEntity->setTitle($backupData['title'] ?? 'Image');
                    if (!empty($backupData['description'])) {
                        $imageEntity->setDescription($backupData['description']);
                    }
                    if (!empty($backupData['authorId'])) {
                        $author = $this->entityManager->find(User::class, $backupData['authorId']);
                        if (null !== $author) {
                            $imageEntity->setAuthor($author);
                        }
                    }
                    if (!empty($backupData['createdAt'])) {
                        $imageEntity->setCreatedAt(new \DateTimeImmutable($backupData['createdAt']));
                    }
                    if (!empty($backupData['updatedAt'])) {
                        $imageEntity->setUpdatedAt(new \DateTimeImmutable($backupData['updatedAt']));
                    }
                    $this->entityManager->persist($imageEntity);
                }

                if (null !== $imageEntity && null !== $backupData) {
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
            /** @var array<string, string> $reverseReplacements */
            $reverseReplacements = array_flip($contentReplacements);
            $pages = $this->pageRepository->findAll();

            foreach ($pages as $page) {
                $pageId = (string) $page->getId();
                $backupData = $pageEntityBackups[$pageId] ?? null;

                if (null !== $backupData) {
                    if (!empty($backupData['featureImageId'])) {
                        $origFeatImage = $this->imageRepository->find($backupData['featureImageId']);
                        if (null !== $origFeatImage) {
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

                if (null !== $backupData) {
                    if (!empty($backupData['imageId'])) {
                        $origSeriesImage = $this->imageRepository->find($backupData['imageId']);
                        if (null !== $origSeriesImage) {
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
            $path = $imageDir.$file;
            if (file_exists($path)) {
                $total += (filesize($path) ?: 0);
            }
        }

        return $total;
    }
}
