<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Image;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    private string $imageDir;
    private string $planFile;
    private string $projectDir;

    /**
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     */
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();

        $this->projectDir = getcwd();
        $this->imageDir = $this->projectDir . '/public/imgs/';
        $this->planFile = $this->projectDir . '/var/image_migration_plan.json';
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
        $images = $this->em->getRepository(Image::class)->findAll();
        $pages = $this->em->getRepository(Page::class)->findAll();
        $seriesList = $this->em->getRepository(Series::class)->findAll();

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
            $safe = strtolower($this->slugger->slug($image->getTitle() ?: 'image'));

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

            if (!isset($used[$image->getId()->toString()]) && !isset($used[$old])) {
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
        foreach ($plan['images'] as &$img) {
            $oldPath = $this->imageDir . $img['old'];
            if (!file_exists($oldPath)) continue;

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
        }

        // -----------------------------
        // Update content
        // -----------------------------
        $pages = $this->em->getRepository(Page::class)->findAll();

        foreach ($pages as $page) {
            $content = $page->getContent();

            foreach ($plan['contentReplacements'] as $old => $new) {
                $content = str_replace('/imgs/' . $old, '/imgs/' . $new, $content);
            }

            foreach ($plan['dedup']['duplicates'] as $dup) {
                $content = str_replace('/imgs/' . $dup['duplicate'], '/imgs/' . $dup['kept'], $content);
            }

            $page->setContent($content);
            $page->setImageSize($this->computeSize($content));
        }

        $seriesList = $this->em->getRepository(Series::class)->findAll();

        foreach ($seriesList as $series) {
            $desc = $series->getDescription();

            foreach ($plan['contentReplacements'] as $old => $new) {
                $desc = str_replace('/imgs/' . $old, '/imgs/' . $new, $desc);
            }

            foreach ($plan['dedup']['duplicates'] as $dup) {
                $desc = str_replace('/imgs/' . $dup['duplicate'], '/imgs/' . $dup['kept'], $desc);
            }

            $series->setDescription($desc);
        }

        $this->em->flush();

        file_put_contents($this->planFile, json_encode($plan, JSON_PRETTY_PRINT));

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

        $pages = $this->em->getRepository(Page::class)->findAll();

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
        $info = getimagesize($src);
        if (!$info) return;

        switch ($info['mime']) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $img = imagecreatefrompng($src);
                break;
            default:
                return;
        }

        imagewebp($img, $dst, 80);
        imagedestroy($img);
    }
}
