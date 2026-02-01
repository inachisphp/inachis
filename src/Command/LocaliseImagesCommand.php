<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Entity\{Image, Page, Series};
use Inachis\Service\Image\{ImageExtractor, ImageLocaliser, ContentImageUpdater};
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:localise-images',
    description: 'Find references to remote images, copy them to [public]/imgs/, and update links',
    aliases: ['app:localize-images'],
)]
class LocaliseImagesCommand extends Command
{
    /**
     * @param EntityManagerInterface $em
     * @param ImageExtractor $extractor
     * @param ImageLocaliser $localiser
     * @param ContentImageUpdater $updater
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ImageExtractor $extractor,
        private ImageLocaliser $localiser,
        private ContentImageUpdater $updater,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Show a report of images to be replaced only. No changes are made.'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return integer
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getTables() as $config) {
            $output->writeln("<info>Processing {$config['table_name']}…</info>");
            $this->processImagesForContentType($config, $output, $input->getOption('dry-run'));
        }
        return Command::SUCCESS;
    }

    /**
     * @param array           $content_type
     * @param OutputInterface $output
     * @param boolean|null    $dryRun
     * @return void
     * @throws Exception
     */
    private function processImagesForContentType(array $content_type, OutputInterface $output, ?bool $dryRun = true): void
    {
        $repo = $this->em->getRepository($content_type['class_name']);
        $results = $repo->getAll(0, 0, ['q.' . $content_type['field'] . ' LIKE :content', ['content' => '%https%']]);

        foreach ($results as $entity) {
            $getter = 'get' . ucfirst($content_type['field']);
            $content = $entity->$getter();
            $images = $content_type['single'] ? [$content] : $this->extractor->extractFromContent($content);
            $changes = ['source' => [], 'destination' => []];

            foreach ($images as $imageUrl) {
                $output->write("Copying $imageUrl... ");
                $localPath = $dryRun ? null : $this->localiser->downloadToLocal($imageUrl);
                if ($localPath) {
                    $changes['source'][] = $imageUrl;
                    $changes['destination'][] = $content_type['single'] ? basename($localPath) : $localPath;
                    $output->writeln('done.');
                } else {
                    $output->writeln('failed.');
                }
            }

            if (!empty($changes['source'])) {
                $this->updater->updateEntity($entity, $content_type['field'], $changes, $content_type['revisions'] ?? false);
            }
        }
    }

    /**
     * @return array[]
     */
    protected function getTables(): array
    {
        return [
            ['class_name' => Image::class, 'field' => 'filename', 'single' => true, 'table_name' => 'image' ],
            ['class_name' => Page::class, 'field' => 'content', 'revisions' => true, 'single' => false, 'table_name' => 'page' ],
            ['class_name' => Series::class, 'field' => 'description', 'single' => false, 'table_name' => 'series' ],
        ];
    }
}
