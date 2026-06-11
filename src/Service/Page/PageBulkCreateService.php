<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page;

use Inachis\Entity\Content\{Page, Url};
use Inachis\Entity\User\User;
use Inachis\Model\BulkCreateData;
use Inachis\Repository\Content\{CategoryRepository, SeriesRepository, TagRepository};
use Inachis\Service\Formatting\UrlNormaliser;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Service for bulk creating pages
 */
class PageBulkCreateService
{
    /**
     * Creates a new instance of the PageBulkCreateService
     *
     * @param EntityManagerInterface $entityManager
     * @param SeriesRepository $seriesRepository
     * @param TagRepository $tagRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SeriesRepository $seriesRepository,
        private TagRepository $tagRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    /**
     * Creates pages in bulk
     *
     * @param BulkCreateData $data
     * @param User $author
     * @return int
     * @throws Exception
     */
    public function create(BulkCreateData $data, User $author): int
    {
        $series = $this->seriesRepository->find($data->seriesId);
        if (!$series) {
            throw new InvalidArgumentException('Series not found');
        }

        if ($series->getFirstDate() === null || $series->getFirstDate() > $data->startDate) {
            $series->setFirstDate($data->startDate);
        }
        if ($series->getLastDate() === null || $series->getLastDate() < $data->endDate) {
            $series->setLastDate($data->endDate);
        }

        $period = new DatePeriod(
            $data->startDate,
            new DateInterval('P1D'),
            (clone $data->endDate)->modify('+1 day')
        );
        $count = 0;

        foreach ($period as $i => $date) {
            $count++;
            $title = $data->title . ($data->addDayNumber ? ' Day ' . ($i + 1) : '');

            $post = new Page($title);
            $post->setPostDate($date);
            $post->setModDate(new DateTimeImmutable());
            $post->setAuthor($author);
            $post->addUrl(new Url(
                $post,
                $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($title)
            ));
            foreach ($data->tags as $newTag) {
                if (Uuid::isValid($newTag)) {
                    $tag = $this->tagRepository->find($newTag);
                } else {
                    $tag = $this->tagRepository->getOrCreate($newTag);
                }

                if ($tag !== null) {
                    $post->addTag($tag);
                }
            }
            foreach($data->categories as $newCategory) {
                $category = null;
                if (Uuid::isValid($newCategory)) {
                    $category = $this->categoryRepository->findOneBy(['id' => $newCategory]);
                }
                if (!empty($category)) {
                    $post->getCategories()->add($category);
                }
            }
            $series->addItem($post);
            $this->entityManager->persist($post);
        }
        if ($count > 0) {
            $this->entityManager->persist($series);
            $this->entityManager->flush();
        }
        return $count;
    }
}