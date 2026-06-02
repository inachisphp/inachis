<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page;

use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Util\TextCleaner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DateTimeImmutable;

/**
 * Content aggregator service
 */
class ContentAggregator
{
    /**
     * Items to show
     */
    public const ITEMS_TO_SHOW = 10;

    /**
     * Constructor
     *
     * @param PageRepository $pageRepository
     * @param SeriesRepository $seriesRepository
     */
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SeriesRepository $seriesRepository,
    ) {}

    /**
     * Get homepage content
     *
     * @return array<string, Page|Series>
     */
    public function getHomepageContent(): array
    {
        $data = [];
        $excludePages = [];

        /** @var Paginator<Series> $series */
        $series = $this->seriesRepository->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [
                'q.lastDate < :postDate AND q.visibility = :visibility',
                [
                    'postDate' => new DateTimeImmutable(),
                    'visibility' => Series::PUBLIC,
                ],
            ],
            [['q.lastDate', 'DESC']]
        );

        foreach ($series as $group) {
            foreach ($group->getItems() as $page) {
                if ($page->getStatus() !== EditorialStatus::PUBLISHED) {
                    $group->getItems()->removeElement($page);
                } else {
                    $excludePages[] = $page->getId();
                }
            }

            $group->setDescription(TextCleaner::strip(
                $group->getDescription(),
                TextCleaner::REMOVE_BLOCKQUOTE_CONTENT | TextCleaner::REMOVE_IMAGE_ALT
            ));

            $lastDate = $group->getLastDate();
            if ($lastDate instanceof DateTimeImmutable) {
                $data['p' . $lastDate->format('Ymd')] = $group;
            }
        }

        $pageQuery = 'q.status = :status AND q.visibility = :visibility AND q.postDate <= :postDate AND q.type = :type';
        $pageParameters = [
            'status'     => EditorialStatus::PUBLISHED,
            'visibility' => Page::PUBLIC,
            'postDate'   => new DateTimeImmutable(),
            'type'       => Page::TYPE_POST,
        ];

        if ($excludePages) {
            $pageQuery .= ' AND q.id NOT IN (:excludedPages)';
            $pageParameters['excludedPages'] = $excludePages;
        }

        /** @var Paginator<Page> $pages */
        $pages = $this->pageRepository->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [$pageQuery, $pageParameters],
            'q.postDate DESC, q.modDate'
        );

        foreach ($pages as $page) {
            $postDate = $page->getPostDate();
            if ($postDate instanceof DateTimeImmutable) {
                $data['p' . $postDate->format('Ymd')] = $page;
            }
        }

        krsort($data);

        return $data;
    }
}
