<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page;

use Inachis\Entity\Page;
use Inachis\Entity\Tag;
use Inachis\Entity\Url;
use Inachis\Entity\User;
use Inachis\Model\BulkCreateData;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\TagRepository;
use Inachis\Util\UrlNormaliser;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class PageBulkCreateService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SeriesRepository $seriesRepository,
        private TagRepository $tagRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function create(BulkCreateData $data, User $author): int
    {
        $series = $this->seriesRepository->find($data->seriesId);
        if (!$series) {
            throw new InvalidArgumentException('Series not found');
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
            $post->setModDate(new DateTime());
            $post->setAuthor($author);
            $post->addUrl(new Url(
                $post,
                $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($title)
            ));

            foreach($data->tags as $newTag) {
                $tag = null;
                if (Uuid::isValid($newTag)) {
                    $tag = $this->tagRepository->findOneBy(['id' => $newTag]);
                }
                if (empty($tag)) {
                    $tag = new Tag($newTag);
                }
                $post->getTags()->add($tag);
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