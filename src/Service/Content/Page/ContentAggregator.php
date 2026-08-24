<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Content\TextCleaner;
use Ramsey\Uuid\Uuid;

/**
 * Content aggregator service.
 */
class ContentAggregator
{
    /**
     * Items to show.
     */
    public const ITEMS_TO_SHOW = 10;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SeriesRepository $seriesRepository,
    ) {
    }

    /**
     * Get homepage content.
     *
     * @return array<string, Page|Series>
     */
    public function getHomepageContent(): array
    {
        $data = [];
        $excludePages = [];

        /** @var Paginator<Series> $series */
        $series = $this->seriesRepository->getAll(
            self::ITEMS_TO_SHOW,
            0,
            [
                'q.lastDate < :postDate AND q.visible = :visible',
                [
                    'postDate' => new \DateTimeImmutable('now'),
                    'visible' => true,
                ],
            ],
            [['q.lastDate', 'DESC']],
        );

        foreach ($series as $group) {
            foreach ($group->getItems() as $page) {
                if (EditorialStatus::PUBLISHED !== $page->getStatus()) {
                    $group->getItems()->removeElement($page);
                } else {
                    $pageId = $page->getId();
                    if (null !== $pageId) {
                        $excludePages[] = $pageId;
                    }
                }
            }

            $group->setDescription(TextCleaner::strip(
                $group->getDescription(),
                TextCleaner::REMOVE_BLOCKQUOTE_CONTENT | TextCleaner::REMOVE_IMAGE_ALT,
            ));

            $lastDate = $group->getLastDate();
            if ($lastDate instanceof \DateTimeImmutable) {
                $data['p'.$lastDate->format('Ymd')] = $group;
            }
        }

        $pages = $this->pageRepository->findHomepagePosts(
            self::ITEMS_TO_SHOW,
            0,
            $excludePages,
        );

        foreach ($pages as $page) {
            $postDate = $page->getPostDate();
            $data['p'.$postDate->format('Ymd')] = $page;
        }

        krsort($data);

        return $data;
    }
}
