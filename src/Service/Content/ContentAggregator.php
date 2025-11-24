<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Content;

use App\Entity\Page;
use App\Entity\Series;
use App\Util\TextCleaner;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ContentAggregator
{
    public const ITEMS_TO_SHOW = 10;

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getHomepageContent(): array
    {
        $data = [];
        $excludePages = [];

        $series = $this->em->getRepository(Series::class)->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [
                'q.lastDate < :postDate',
                ['postDate' => new DateTime()]
            ],
            [['q.lastDate', 'DESC']]
        );

        foreach ($series as $group) {
            foreach ($group->getItems() as $page) {
                $excludePages[] = $page->getId();
            }

            $group->setDescription(TextCleaner::strip(
                $group->getDescription(),
                TextCleaner::REMOVE_BLOCKQUOTE_CONTENT | TextCleaner::REMOVE_IMAGE_ALT
            ));

            $data[$group->getLastDate()->format('Ymd')] = $group;
        }

        $pageQuery = 'q.status = :status AND q.visibility = :visibility AND q.postDate <= :postDate AND q.type = :type';
        $pageParameters = [
            'status'     => Page::PUBLISHED,
            'visibility' => Page::PUBLIC,
            'postDate'   => new DateTime(),
            'type'       => Page::TYPE_POST,
        ];

        if ($excludePages) {
            $pageQuery .= ' AND q.id NOT IN (:excludedPages)';
            $pageParameters['excludedPages'] = $excludePages;
        }

        $pages = $this->em->getRepository(Page::class)->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [$pageQuery, $pageParameters],
            'q.postDate DESC, q.modDate'
        );

        foreach ($pages as $page) {
            $data[$page->getPostDate()->format('Ymd')] = $page;
        }

        krsort($data);

        return $data;
    }
}
