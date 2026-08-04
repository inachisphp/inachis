<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for content selector dialog.
 */
class ContentSelectorController extends AbstractInachisController
{
    /**
     * Get content list.
     */
    #[Route('/incp/ax/contentSelector/get', methods: ['POST'])]
    public function contentList(
        Request $request,
        SeriesRepository $seriesRepository,
        PageRepository $pageRepository,
    ): Response {
        /**
         * @var array{
         *   categories?:array<string>,
         *   tags?:array<string>,
         *   status?:string,
         *   visible?:bool,
         *   keyword?:string,
         *   excludeIds?:list<string>,
         *   fromDate?:\DateTimeImmutable,
         *   toDate?:\DateTimeImmutable
         * }
         */
        $filters = array_filter($request->request->all('filters'));

        /** @var string $seriesId */
        $seriesId = $request->request->getString('seriesId', '');
        if ('' !== $seriesId) {
            $series = $seriesRepository->find($seriesId);
            if (null !== $series) {
                $items = $series->getItems();
                if (!$items->isEmpty()) {
                    $filters['excludeIds'] = [];
                    /** @var \Inachis\Entity\Content\Page $item */
                    foreach ($items as $item) {
                        $filters['excludeIds'][] = $item->getId()?->toString() ?: '';
                    }
                }
            }
        }
        $limit = $request->request->getInt('limit', 25);
        $offset = $request->request->getInt('offset', 0);

        return $this->render('inadmin/dialog/content-selector.html.twig', [
            'pages' => $pageRepository->getFilteredOfTypeByPostDate(
                $filters,
                '*',
                $limit,
                $offset,
                'title asc',
            ),
            'query' => [
                'filters' => $filters,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    #[Route('/incp/ax/contentSelector/save', methods: ['POST'])]
    public function saveContent(
        Request $request,
        SeriesRepository $seriesRepository,
        PageRepository $pageRepository,
    ): Response {
        $ids = $request->request->all('ids');
        $seriesId = $request->request->getString('seriesId', '');
        $series = $seriesRepository->find($seriesId);
        if (empty($ids) || null === $series) {
            return new Response('No change', Response::HTTP_NO_CONTENT);
        }

        foreach ($ids as $pageId) {
            $page = $pageRepository->find($pageId);
            if (!$page instanceof \Inachis\Entity\Content\Page) {
                continue;
            }

            $series->addItem($page);

            $pageDate = $page->getPostDate();
            $firstDate = $series->getFirstDate();
            $lastDate = $series->getLastDate();

            if (null === $firstDate || $pageDate < $firstDate) {
                $series->setFirstDate($pageDate);
            }
            if (null === $lastDate || $pageDate > $lastDate) {
                $series->setLastDate($pageDate);
            }
        }
        $series->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($series);
        $this->entityManager->flush();

        return new Response('Saved', Response::HTTP_CREATED);
    }
}
