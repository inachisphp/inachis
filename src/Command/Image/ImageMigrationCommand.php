<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Image;

use Inachis\Entity\Media\Image;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'inachis:images:migrate',
    description: 'Full image migration: rename, deduplicate, WebP optimise, and update references'
)]
class ImageMigrationCommand extends Command
{
    private const MAX_DIMENSION = 1024;
    
    private string $checkpointFile;
    private string $imageDir;
    private string $planFile;
    private string $projectDir;

    /**
     * @param SluggerInterface $slugger
     */
    public function __construct(
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private SluggerInterface $slugger
    ) {
        parent::__construct();

        $this->projectDir = getcwd();
        $this->imageDir = $this->projectDir . '/public/imgs/';
        $this->planFile = $this->projectDir . '/var/image_migration_plan.json';
        $this->checkpointFile = $this->projectDir . '/var/image_migration_checkpoint.json';
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'scan | apply | rollback');
    }

    /**
     * Execute the command.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return match ($input->getArgument('mode')) {
            'scan' => $this->scan($output),
            'apply' => $this->apply($output),
            'rollback' => $this->rollback($output),
            default => Command::FAILURE
        };
    }

    /**
     * Scan the image directory for unused and broken images.
     * 
     * @param OutputInterface $output
     * @return int
     */
    private function scan(OutputInterface $output): int
    {
        $images = $this->imageRepository->findAll();
        $pages = $this->pageRepository->findAll();
        $seriesList = $this->seriesRepository->findAll();

        $plan = [
            'images' => [],
            'contentReplacements' => [],
            'unused' => [],
            'broken' => [],
            'dedup' => [
                'map' => [],
                'duplicates' => []
            ]
        ];

        $used = [];
        $checksumMap = [];

        // -----------------------------
        // Detect usage
        // -----------------------------
        foreach ($pages as $page) {
            if ($page->getFeatureImage()) {
                $used[$page->getFeatureImage()->getId()->toString()] = true;
            }

            $this->extractRefs($page->getContent(), $used);
        }

        foreach ($seriesList as $series) {
            if ($series->getImage()) {
                $used[$series->getImage()->getId()->toString()] = true;
            }

            $this->extractRefs($series->getDescription(), $used);
        }

        // // -----------------------------
        // // Deduplication by checksum
        // // -----------------------------
        // foreach ($images as $image) {
        //     $file = $image->getFilename();
        //     if (!$file) continue;

        //     $path = $this->imageDir . $file;
        //     if (!file_exists($path)) continue;

        //     $checksum = hash_file('sha256', $path);

        //     if (!isset($checksumMap[$checksum])) {
        //         $checksumMap[$checksum] = $file;
        //         $plan['dedup']['map'][$checksum] = $file;
        //     } else {
        //         $plan['dedup']['duplicates'][] = [
        //             'duplicate' => $file,
        //             'kept' => $checksumMap[$checksum]
        //         ];
        //     }
        // }
        // dump($plan['dedup']);
        // exit;

        // -----------------------------
        // Rename planning
        // -----------------------------
        foreach ($images as $image) {
            $old = $image->getFilename();
            if (!$old) continue;

            $ext = strtolower(pathinfo($old, PATHINFO_EXTENSION));
            $safe = strtolower($this->slugger->slug($image->getTitle() . '-' . uniqid() ?: 'image'));

            $new = $safe . '.' . $ext;

            $path = $this->imageDir . $old;
            $checksum = (file_exists($path)) ? hash_file('sha256', $path) : null;

            $plan['images'][] = [
                'id' => $image->getId()->toString(),
                'old' => $old,
                'new' => $new,
                'ext' => $ext,
                'convertToWebp' => in_array($ext, ['jpg', 'jpeg', 'png']),
                'oldChecksum' => $checksum,
                'newChecksum' => null
            ];

            $plan['contentReplacements'][$old] = $new;

            if (!isset($used[$image->getId()->toString()])) {
                $plan['unused'][] = $old;
            }
        }

        // -----------------------------
        // Broken references
        // -----------------------------
        foreach ($pages as $page) {
            $broken = $this->findBroken($page->getContent());
            if ($broken) {
                $plan['broken']['page:' . $page->getId()] = $broken;
            }
        }

        foreach ($seriesList as $series) {
            $broken = $this->findBroken($series->getDescription());
            if ($broken) {
                $plan['broken']['series:' . $series->getId()] = $broken;
            }
        }

        file_put_contents($this->planFile, json_encode($plan, JSON_PRETTY_PRINT));

        $output->writeln('<info>Scan complete</info>');
        $output->writeln('Unused: ' . count($plan['unused']));
        $output->writeln('Duplicates: ' . count($plan['dedup']['duplicates']));
        $output->writeln('Broken refs: ' . count($plan['broken']));

        return Command::SUCCESS;
    }

    /**
     * Apply the plan to the image directory.
     * 
     * @param OutputInterface $output
     * @return int
     */
    private function apply(OutputInterface $output): int
    {
        if (!file_exists($this->planFile)) {
            $output->writeln('<error>No plan file found</error>');
            return Command::FAILURE;
        }

        $plan = json_decode(file_get_contents($this->planFile), true);

        $checkpoint = file_exists($this->checkpointFile)
            ? json_decode(file_get_contents($this->checkpointFile), true)
            : ['imageIndex' => 0, 'pageIndex' => 0, 'seriesIndex' => 0];

        // -----------------------------
        // Deduplication
        // -----------------------------
        // foreach ($plan['dedup']['duplicates'] as $dup) {
        //     $dupPath = $this->imageDir . $dup['duplicate'];
        //     $keepPath = $this->imageDir . $dup['kept'];

        //     if (file_exists($dupPath) && file_exists($keepPath)) {
        //         unlink($dupPath);

        //         $image = $this->em->getRepository(Image::class)
        //             ->findOneBy(['filename' => $dup['duplicate']]);

        //         if ($image) {
        //             $canonical = $this->em->getRepository(Image::class)
        //                 ->findOneBy(['filename' => $dup['kept']]);

        //             if ($canonical) {
        //                 $image->setFilename($dup['kept']);
        //                 $image->setChecksum($canonical->getChecksum());
        //             }
        //         }
        //     }
        // }

        // -----------------------------
        // Rename + WebP
        // -----------------------------
        $output->writeln('<info>Processing images...</info>');

        $images = $plan['images'];
        $imageProgress = new ProgressBar($output, count($images));
        $imageProgress->advance($checkpoint['imageIndex']);

        for ($i = $checkpoint['imageIndex']; $i < count($images); $i++) {
            $img = &$images[$i];

            $oldPath = $this->imageDir . $img['old'];
            if (!file_exists($oldPath)) {
                $imageProgress->advance();
                continue;
            }

            $newPath = $this->imageDir . $img['new'];

            if ($img['convertToWebp']) {
                $webpPath = preg_replace('/\.\w+$/', '.webp', $newPath);

                $this->convertWebp($oldPath, $webpPath);

                if (file_exists($webpPath) && filesize($webpPath) < filesize($oldPath)) {
                    unlink($oldPath);
                    $newPath = $webpPath;
                    $img['new'] = basename($webpPath);
                } else {
                    if (file_exists($webpPath)) unlink($webpPath);
                    rename($oldPath, $newPath);
                }
            } else {
                rename($oldPath, $newPath);
            }

            $checksum = hash_file('sha256', $newPath);

            $image = $this->em->find(Image::class, $img['id']);
            $image->setFilename($img['new']);
            $image->setChecksum($checksum);
            $img['newChecksum'] = $checksum;

            $this->saveCheckpoint($i + 1, $checkpoint['pageIndex'], $checkpoint['seriesIndex']);
            $imageProgress->advance();
        }
        $imageProgress->finish();
        $output->writeln('');


        // -----------------------------
        // Update content
        // -----------------------------
        $pages = $this->pageRepository->findAll();
        
        $output->writeln('<info>Updating pages...</info>');

        $pageProgress = new ProgressBar($output, count($pages));
        $pageProgress->advance($checkpoint['pageIndex']);

        for ($i = $checkpoint['pageIndex']; $i < count($pages); $i++) {
            $page = $pages[$i];
            $content = $page->getContent();

            foreach ($plan['contentReplacements'] as $old => $new) {
                $content = str_replace('/imgs/' . $old, '/imgs/' . $new, $content);
            }

            foreach ($plan['dedup']['duplicates'] as $dup) {
                $content = str_replace('/imgs/' . $dup['duplicate'], '/imgs/' . $dup['kept'], $content);
            }

            $page->setContent($content);
            $page->setImageSize($this->computeSize($content));

            $this->saveCheckpoint($checkpoint['imageIndex'], $i + 1, $checkpoint['seriesIndex']);
            $pageProgress->advance();
        }

        $pageProgress->finish();
        $output->writeln('');

        $output->writeln('<info>Updating series...</info>');

        $seriesList = $this->seriesRepository->findAll();

        $seriesProgress = new ProgressBar($output, count($seriesList));
        $seriesProgress->advance($checkpoint['seriesIndex']);

        for ($i = $checkpoint['seriesIndex']; $i < count($seriesList); $i++) {
            $series = $seriesList[$i];
            $desc = $series->getDescription();

            foreach ($plan['contentReplacements'] as $old => $new) {
                $desc = str_replace('/imgs/' . $old, '/imgs/' . $new, $desc);
            }

            foreach ($plan['dedup']['duplicates'] as $dup) {
                $desc = str_replace('/imgs/' . $dup['duplicate'], '/imgs/' . $dup['kept'], $desc);
            }

            $series->setDescription($desc);

            $this->saveCheckpoint($checkpoint['imageIndex'], $checkpoint['pageIndex'], $i + 1);
            $seriesProgress->advance();
        }

        $seriesProgress->finish();
        $output->writeln('');

        $this->em->flush();
        @unlink($this->checkpointFile);

        $output->writeln('<info>Apply complete</info>');

        // file_put_contents($this->planFile, json_encode($plan, JSON_PRETTY_PRINT));

        $output->writeln('<info>Apply complete</info>');

        return Command::SUCCESS;
    }

    /**
     * Rollback the plan to the image directory.
     * 
     * @param OutputInterface $output
     * @return int
     */
    private function rollback(OutputInterface $output): int
    {
        if (!file_exists($this->planFile)) {
            $output->writeln('<error>No plan</error>');
            return Command::FAILURE;
        }

        $plan = json_decode(file_get_contents($this->planFile), true);

        foreach ($plan['images'] as $img) {
            $oldPath = $this->imageDir . $img['old'];
            $newPath = $this->imageDir . $img['new'];

            if (file_exists($newPath)) {
                rename($newPath, $oldPath);
            }

            $image = $this->em->find(Image::class, $img['id']);
            $image->setFilename($img['old']);
        }

        $reverse = array_flip($plan['contentReplacements']);

        $pages = $this->pageRepository->findAll();

        foreach ($pages as $page) {
            $content = $page->getContent();

            foreach ($reverse as $new => $old) {
                $content = str_replace('/imgs/' . $new, '/imgs/' . $old, $content);
            }

            $page->setContent($content);
        }

        $this->em->flush();

        $output->writeln('<info>Rollback complete</info>');

        return Command::SUCCESS;
    }

    /**
     * Extract references to images from the content.
     * 
     * @param string|null $content
     * @param array $used
     * @return void
     */
    private function extractRefs(?string $content, array &$used): void
    {
        if (!$content) return;

        preg_match_all('/\/imgs\/([^)]+)/', $content, $m);
        foreach ($m[1] as $f) {
            $used[$f] = true;
        }
    }

    /**
     * Find broken references to images in the content.
     * 
     * @param string|null $content
     * @return array
     */
    private function findBroken(?string $content): array
    {
        if (!$content) return [];

        $broken = [];

        preg_match_all('/\/imgs\/([^)]+)/', $content, $m);

        foreach ($m[1] as $f) {
            if (!file_exists($this->imageDir . $f)) {
                $broken[] = $f;
            }
        }

        return $broken;
    }

    /**
     * Compute the total size of images in the content.
     * 
     * @param string|null $content
     * @return int
     */
    private function computeSize(?string $content): int
    {
        if (!$content) return 0;

        $total = 0;

        preg_match_all('/\/imgs\/([^)]+)/', $content, $m);

        foreach ($m[1] as $f) {
            $p = $this->imageDir . $f;
            if (file_exists($p)) {
                $total += filesize($p);
            }
        }

        return $total;
    }

    /**
     * Convert an image to WebP.
     * 
     * @param string $src
     * @param string $dst
     * @return void
     */
    private function convertWebp(string $src, string $dst): void
    {
        if (class_exists(\Imagick::class)) {
            try {
                $img = new \Imagick($src);
                $img->thumbnailImage(self::MAX_DIMENSION, self::MAX_DIMENSION, true);
                $img->setImageFormat('webp');
                $img->setImageCompressionQuality(80);
                $img->writeImage($dst);
                $img->clear();
                $img->destroy();

                return;
            } catch (\Throwable $e) {}
        }

        if (!function_exists('imagewebp')) {
            return;
        }

        $info = getimagesize($src);
        if (!$info) return;

        [$w, $h] = $info;

        switch ($info['mime']) {
            case 'image/jpeg':
                if (!function_exists('imagecreatefromjpeg')) return;
                $img = imagecreatefromjpeg($src);
                break;
            case 'image/png':
                if (!function_exists('imagecreatefrompng')) return;
                $img = imagecreatefrompng($src);
                break;
            default:
                return;
        }

        if (!$img) return;

        $ratio = min(self::MAX_DIMENSION / $w, self::MAX_DIMENSION / $h, 1);

        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);

        $tmp = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        imagewebp($tmp, $dst, 80);

        imagedestroy($img);
        imagedestroy($tmp);
    }

    /**
     * Save the current state to a checkpoint file.
     * 
     * @param int $imageIndex
     * @param int $pageIndex
     * @param int $seriesIndex
     * @return void
     */
    private function saveCheckpoint(
        int $imageIndex,
        int $pageIndex,
        int $seriesIndex
    ): void {
        file_put_contents($this->checkpointFile, json_encode([
            'imageIndex' => $imageIndex,
            'pageIndex' => $pageIndex,
            'seriesIndex' => $seriesIndex,
        ]));
    }
}
