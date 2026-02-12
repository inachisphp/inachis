<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Exception;
use Inachis\Entity\{Image, Page, Series};
use Inachis\Service\Image\{ImageExtractor, ImageLocaliser, ContentImageUpdater};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to localise images
 */
#[AsCommand(
    name: 'app:localise-images',
    description: 'Find references to remote images, copy them to [public]/imgs/, and update links',
    aliases: ['app:localize-images'],
)]
class LocaliseImagesCommand extends Command
{
    /**
     * Constructor
     * 
     * @param EntityManagerInterface $entityManager
     * @param ImageExtractor $imageExtractor
     * @param ImageLocaliser $imageLocaliser
     * @param ContentImageUpdater $contentImageUpdater
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageExtractor $imageExtractor,
        private ImageLocaliser $imageLocaliser,
        private ContentImageUpdater $contentImageUpdater,
    ) {
        parent::__construct();
    }

    /**
     * Configure the command
     */
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
     * Execute the command
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getTables() as $config) {
            $output->writeln("<info>Processing {$config['table_name']}…</info>");
            $this->processImagesForContentType($config, $output, (bool) $input->getOption('dry-run'));
        }
        return Command::SUCCESS;
    }

    /**
     * Process images for a content type
     * 
     * @param array{class_name: class-string, field: string, single: bool, table_name: string, revisions?: bool} $content_type
     * @param OutputInterface $output
     * @param bool $dryRun
     * @return void
     * @throws Exception
     */
    private function processImagesForContentType(array $content_type, OutputInterface $output, bool $dryRun = true): void
    {
        /** @var \Doctrine\ORM\EntityRepository<Image|Page|Series> $repository */
        $repository = $this->entityManager->getRepository($content_type['class_name']);
        /** @var (Image|Page|Series)[] $results */
        $results = $repository->getAll(0, 0, ['q.' . $content_type['field'] . ' LIKE :content', ['content' => '%https%']]);

        foreach ($results as $entity) {
            $getter = 'get' . ucfirst($content_type['field']);
            /** @var string $content */
            $content = $entity->$getter();
            /** @var string[] $images */
            $images = $content_type['single'] ? [$content] : $this->imageExtractor->extractFromContent($content);
            /** @var array{source: string[], destination: string[]} $changes */
            $changes = ['source' => [], 'destination' => []];

            foreach ($images as $imageUrl) {
                $output->write("Copying $imageUrl... ");
                $localPath = $dryRun ? null : $this->imageLocaliser->downloadToLocal($imageUrl);
                if ($localPath) {
                    $changes['source'][] = $imageUrl;
                    $changes['destination'][] = $content_type['single'] ? basename($localPath) : $localPath;
                    $output->writeln('done.');
                } else {
                    $output->writeln('failed.');
                }
            }

            if (!empty($changes['source'])) {
                $this->contentImageUpdater->updateEntity($entity, $content_type['field'], $changes, $content_type['revisions'] ?? false);
            }
        }
    }

    /**
     * Get the tables to process
     * 
     * @return array<array{
     *     class_name: class-string,
     *     field: string,
     *     single: bool,
     *     table_name: string,
     *     revisions?: bool
     * }>
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
