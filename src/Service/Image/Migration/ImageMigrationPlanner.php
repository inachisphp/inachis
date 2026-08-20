<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageMigrationPlanner
{
    private const MAX_DIMENSION = 1024;

    public function __construct(
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private SluggerInterface $slugger,
        private ImageProcessor $imageProcessor,
        private MarkdownImageRewriter $markdownRewriter,
    ) {
    }

    /**
     * Generate complete image migration plan.
     *
     * @return array<string, mixed>
     */
    public function generatePlan(
        string $imageDir,
        bool $noWebp,
        bool $noDedup,
        bool $noResize,
    ): array {
        $images = $this->imageRepository->findBy([], ['id' => 'ASC']);
        $pages = $this->pageRepository->findBy([], ['id' => 'ASC']);
        $seriesList = $this->seriesRepository->findBy([], ['id' => 'ASC']);

        $usedImageIds = [];
        $referencedFiles = [];
        $brokenRefs = [];

        // 1. Scan usage in Pages
        $pageEntityBackups = [];
        foreach ($pages as $page) {
            $pageId = (string) $page->getId();
            $featImageId = $page->getFeatureImage()?->getId()?->toString();

            if (!empty($featImageId)) {
                $usedImageIds[$featImageId] = true;
                if (!empty($page->getFeatureImage()->getFilename())) {
                    $referencedFiles[$page->getFeatureImage()->getFilename()] = true;
                }
            }

            $pageEntityBackups[$pageId] = [
                'id' => $pageId,
                'title' => $page->getTitle(),
                'featureImageId' => $featImageId,
                'content' => $page->getContent(),
            ];

            $extracted = $this->markdownRewriter->extractImageReferences($page->getContent());
            foreach ($extracted as $file) {
                $referencedFiles[$file] = true;
                if (!file_exists($imageDir.$file)) {
                    $brokenRefs[] = [
                        'entity' => 'Page',
                        'id' => $pageId,
                        'title' => $page->getTitle(),
                        'filename' => $file,
                    ];
                }
            }
        }

        // 2. Scan usage in Series
        $seriesEntityBackups = [];
        foreach ($seriesList as $series) {
            $seriesId = (string) $series->getId();
            $sImageId = $series->getImage()?->getId()?->toString();

            if (!empty($sImageId)) {
                $usedImageIds[$sImageId] = true;
                if (!empty($series->getImage()->getFilename())) {
                    $referencedFiles[$series->getImage()->getFilename()] = true;
                }
            }

            $seriesEntityBackups[$seriesId] = [
                'id' => $seriesId,
                'title' => $series->getTitle(),
                'imageId' => $sImageId,
                'description' => $series->getDescription(),
            ];

            $extracted = $this->markdownRewriter->extractImageReferences($series->getDescription());
            foreach ($extracted as $file) {
                $referencedFiles[$file] = true;
                if (!file_exists($imageDir.$file)) {
                    $brokenRefs[] = [
                        'entity' => 'Series',
                        'id' => $seriesId,
                        'title' => $series->getTitle(),
                        'filename' => $file,
                    ];
                }
            }
        }

        // 3. Pixel Deduplication Mapping
        $pixelMap = [];
        $duplicates = [];
        $canonicalMap = [];

        foreach ($images as $image) {
            $imageId = (string) $image->getId();
            $filename = $image->getFilename();
            if (empty($filename)) {
                continue;
            }

            $filePath = $imageDir.$filename;
            if (!file_exists($filePath)) {
                continue;
            }

            $pixelHash = $this->imageProcessor->computePixelChecksum($filePath);
            if (empty($pixelHash)) {
                continue;
            }

            if (!$noDedup && isset($pixelMap[$pixelHash])) {
                $canonical = $pixelMap[$pixelHash];
                $duplicates[] = [
                    'duplicateId' => $imageId,
                    'duplicateFilename' => $filename,
                    'canonicalId' => $canonical['id'],
                    'canonicalFilename' => $canonical['filename'],
                    'pixelHash' => $pixelHash,
                ];
                $canonicalMap[$imageId] = $canonical['id'];
            } else {
                $pixelMap[$pixelHash] = [
                    'id' => $imageId,
                    'filename' => $filename,
                ];
            }
        }

        // 4. Image Entity State Serialization & Candidate Slug Generation
        $usedTargetFilenames = [];
        $imagePlans = [];
        $imageEntityBackups = [];
        $contentReplacements = [];
        $unusedImages = [];

        $totalOriginalBytes = 0;
        $totalEstimatedFinalBytes = 0;
        $totalOriginalMegapixels = 0.0;
        $totalFinalMegapixels = 0.0;

        foreach ($images as $image) {
            $imageId = (string) $image->getId();
            $oldFilename = $image->getFilename();
            if (empty($oldFilename)) {
                continue;
            }

            $filePath = $imageDir.$oldFilename;
            $fileExists = file_exists($filePath);
            $filesize = $fileExists ? (filesize($filePath) ?: 0) : 0;
            $checksum = $image->getChecksum() ?? ($fileExists ? (hash_file('sha256', $filePath) ?: '') : '');
            $totalOriginalBytes += $filesize;

            $isDuplicate = isset($canonicalMap[$imageId]);

            // Save full entity backup data for exact DB rollback
            $imageEntityBackups[$imageId] = [
                'id' => $imageId,
                'title' => $image->getTitle(),
                'description' => $image->getDescription(),
                'filename' => $oldFilename,
                'filetype' => $image->getFiletype(),
                'filesize' => $filesize,
                'checksum' => $checksum,
                'dimensionX' => $image->getDimensionX(),
                'dimensionY' => $image->getDimensionY(),
                'altText' => $image->getAltText(),
                'authorId' => $image->getAuthor()?->getId()?->toString(),
                'createdAt' => $image->getCreatedAt()->format('c'),
                'updatedAt' => $image->getUpdatedAt()->format('c'),
            ];

            $origExt = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION));

            $imgInfo = $fileExists ? @getimagesize($filePath) : false;
            $origW = $imgInfo[0] ?? $image->getDimensionX();
            $origH = $imgInfo[1] ?? $image->getDimensionY();
            $origMP = ($origW * $origH) / 1_000_000.0;
            $totalOriginalMegapixels += $origMP;

            $targetW = $origW;
            $targetH = $origH;
            $needsResize = !$noResize && ($origW > self::MAX_DIMENSION || $origH > self::MAX_DIMENSION);

            if ($needsResize && $origW > 0 && $origH > 0) {
                $ratio = min(self::MAX_DIMENSION / $origW, self::MAX_DIMENSION / $origH, 1.0);
                $targetW = (int) round($origW * $ratio);
                $targetH = (int) round($origH * $ratio);
            }
            $targetMP = ($targetW * $targetH) / 1_000_000.0;
            $totalFinalMegapixels += $targetMP;

            // Accurate WebP size estimation with guaranteed temp file cleanup
            $willConvertToWebp = false;
            $estimatedFilesize = $filesize;
            $finalExt = $origExt;

            if (!$noWebp && in_array($origExt, ['jpg', 'jpeg', 'png', 'heic', 'heif', 'webp'], true) && 'svg' !== $origExt && $fileExists) {
                $tempWebpPath = tempnam(sys_get_temp_dir(), 'scan_webp_').'.webp';
                try {
                    if ($this->imageProcessor->convertToWebp($filePath, $tempWebpPath)) {
                        $webpSize = filesize($tempWebpPath) ?: 0;
                        if ($webpSize < $filesize) {
                            $willConvertToWebp = true;
                            $estimatedFilesize = $webpSize;
                            $finalExt = 'webp';
                        }
                    }
                } finally {
                    if (file_exists($tempWebpPath)) {
                        @unlink($tempWebpPath);
                    }
                }
            }
            $totalEstimatedFinalBytes += $estimatedFilesize;

            // Deterministic Slug Generation & Collision Resolution on FINAL Extension
            $title = $image->getTitle();
            $baseSlug = !empty($title) ? $this->slugger->slug($title)->lower()->toString() : '';
            if (empty($baseSlug)) {
                $filenameNoExt = pathinfo($oldFilename, PATHINFO_FILENAME);
                $baseSlug = $this->slugger->slug($filenameNoExt)->lower()->toString();
                if (empty($baseSlug)) {
                    $baseSlug = 'image';
                }
            }

            $candidateFilename = $baseSlug.'.'.$finalExt;
            $counter = 2;
            while (isset($usedTargetFilenames[$candidateFilename])) {
                $candidateFilename = $baseSlug.'-'.$counter.'.'.$finalExt;
                ++$counter;
            }
            $usedTargetFilenames[$candidateFilename] = true;

            $pixelReductionPercent = $origMP > 0 ? round((1.0 - ($targetMP / $origMP)) * 100.0, 1) : 0.0;

            $imagePlans[] = [
                'id' => $imageId,
                'title' => $title,
                'oldFilename' => $oldFilename,
                'newFilename' => $candidateFilename,
                'origExt' => $origExt,
                'finalExt' => $finalExt,
                'convertToWebp' => $willConvertToWebp,
                'oldChecksum' => $checksum,
                'oldFilesize' => $filesize,
                'estimatedFilesize' => $estimatedFilesize,
                'origWidth' => $origW,
                'origHeight' => $origH,
                'origMegapixels' => round($origMP, 2),
                'targetWidth' => $targetW,
                'targetHeight' => $targetH,
                'targetMegapixels' => round($targetMP, 2),
                'pixelReductionPercent' => $pixelReductionPercent,
                'needsResize' => $needsResize,
                'isDuplicate' => $isDuplicate,
                'canonicalId' => $canonicalMap[$imageId] ?? null,
            ];

            $contentReplacements[$oldFilename] = $candidateFilename;

            $isReferencedInEntity = isset($usedImageIds[$imageId]);
            $isReferencedInMarkdown = isset($referencedFiles[$oldFilename]);
            if (!$isReferencedInEntity && !$isReferencedInMarkdown) {
                $unusedImages[] = [
                    'id' => $imageId,
                    'filename' => $oldFilename,
                    'size' => $filesize,
                ];
            }
        }

        // Scan physical orphan files
        $diskFiles = glob($imageDir.'*');
        if (false !== $diskFiles) {
            $dbFiles = array_filter(array_map(fn ($img) => $img->getFilename(), $images));
            $dbFilesSet = array_flip($dbFiles);

            foreach ($diskFiles as $diskFilePath) {
                if (!is_file($diskFilePath)) {
                    continue;
                }
                $basename = basename($diskFilePath);
                if (!isset($dbFilesSet[$basename]) && !isset($referencedFiles[$basename])) {
                    $unusedImages[] = [
                        'id' => 'orphan_file',
                        'filename' => $basename,
                        'size' => filesize($diskFilePath) ?: 0,
                    ];
                }
            }
        }

        $scanTimestamp = date('c');
        $planMeta = [
            'scanTimestamp' => $scanTimestamp,
            'imageCount' => count($images),
            'pageCount' => count($pages),
            'seriesCount' => count($seriesList),
        ];

        $planHash = hash('sha256', (string) json_encode($imagePlans));
        $planMeta['planHash'] = $planHash;

        return [
            'metadata' => $planMeta,
            'options' => [
                'noWebp' => $noWebp,
                'noDedup' => $noDedup,
                'noResize' => $noResize,
            ],
            'entityBackups' => [
                'images' => $imageEntityBackups,
                'pages' => $pageEntityBackups,
                'series' => $seriesEntityBackups,
            ],
            'images' => $imagePlans,
            'contentReplacements' => $contentReplacements,
            'duplicates' => $duplicates,
            'unused' => $unusedImages,
            'broken' => $brokenRefs,
            'stats' => [
                'totalScanned' => count($images),
                'totalOriginalBytes' => $totalOriginalBytes,
                'totalEstimatedFinalBytes' => $totalEstimatedFinalBytes,
                'totalOriginalMegapixels' => round($totalOriginalMegapixels, 2),
                'totalFinalMegapixels' => round($totalFinalMegapixels, 2),
            ],
        ];
    }
}
