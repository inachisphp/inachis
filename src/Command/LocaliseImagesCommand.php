<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Command;

use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Series;
use App\Repository\RevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
//use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


//use Symfony\Component\Console\Input\InputOption;
//use Symfony\Component\Console\Question\Question;
//use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:localise-images',
    description: 'Find references to remote images, copy them to [public]/imgs/, and update links',
    aliases: ['app:localize-images'],
)]
class LocaliseImagesCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    private const MARKDOWN_IMAGE = '/\!\[[^\]]*]\((https?:\/\/(?:[^\)]+))\)/';

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getTables() as $type) {
            $this->processImagesForContentType($type, $input->getOption('dry-run'));
        }
        return Command::SUCCESS;
    }

    /**
     * @param array $content_type
     * @param bool|null $dry_run
     * @return void
     */
    private function processImagesForContentType(array $content_type, ?bool $dry_run = true): void
    {
        echo sprintf('Processing %s', ucfirst($content_type['table_name'])) . PHP_EOL;

        $results = $this->entityManager->getRepository($content_type['class_name'])->getAll(
            0,
            0,
            [
                'q.' . $content_type['field'] . ' LIKE :content',
                [
                    'content' => '%https%',
                ]
            ]
        );
        foreach ($results as $result) {
            $changes = [
                'source',
                'destination'
            ];
            $get_function = 'get' . ucfirst($content_type['field']);
            $set_function = 'set' . ucfirst($content_type['field']);

            if (!$content_type['single']) {
                $images = $this->findImagesInContent($result->$get_function());
            } else {
                $images = [ $result->$get_function() ];
            }
            foreach ($images as $image) {
                $filename = basename(parse_url($image, PHP_URL_PATH));
                echo sprintf('Copying %s to public/imgs/%s… ', $image, $filename);
                if (!$dry_run) {
                    if (file_put_contents('/tmp/' . $filename, fopen($image, 'r')) > 0) {
                        if (pathinfo('/tmp/' . $filename, PATHINFO_EXTENSION) === '') {
                            $extension = explode('/', mime_content_type('/tmp/' . $filename))[1];
                            rename('/tmp/' . $filename, '/tmp/' . $filename . '.' . $extension);
                            $filename = $filename . '.' . $extension;
                            echo sprintf('Added %s extension… ', $extension);
                        }
                        if (filesize('/tmp/' . $filename) > 0 &&
                            rename('/tmp/' . $filename, getcwd() . '/public/imgs/' . $filename)) {
                            $changes['source'][] = $image;
                            if (!$content_type['single']) {
                                $changes['destination'][] = '/imgs/' . $filename;
                            } else {
                                $changes['destination'][] = $filename;
                            }
                            echo '… ';
                        } else {
                            echo 'Failed to move file' . PHP_EOL;
                            continue;
                        }
                    } else {
                        echo 'Failed to write file' . PHP_EOL;
                        continue;
                    }
                }
                echo 'Done' . PHP_EOL;

                sleep(120);
            }
            if (!empty($changes['source'])) {
                $updated_content = str_replace($changes['source'], $changes['destination'], $result->$get_function());
                $result->$set_function($updated_content);
                $result->setModDate(new \DateTime());
                if (isset($content_type['revisions']) && $content_type['revisions']) {
                    $revision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($result);
                    $revision = $revision->setAction(RevisionRepository::UPDATED);
                    $this->entityManager->persist($revision);
                }
                $this->entityManager->persist($result);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param string $content
     * @return array
     */
    private function findImagesInContent(string $content): array
    {
        preg_match_all(self::MARKDOWN_IMAGE, $content, $images);
        return $images[1];
    }

    /**
     * @return array[]
     */
    protected function getTables(): array
    {
        return [
            [
                'class_name' => Image::class,
                'field' => 'filename',
                'single' => true,
                'table_name' => 'image',
            ],
            [
                'class_name' => Page::class,
                'field' => 'content',
                'revisions' => true,
                'single' => false,
                'table_name' => 'page',
            ],
            [
                'class_name' => Series::class,
                'field' => 'description',
                'single' => false,
                'table_name' => 'series',
            ],
        ];
    }
}