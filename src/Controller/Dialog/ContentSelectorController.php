<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for content selector dialog
 */
#[IsGranted('ROLE_ADMIN')]
class ContentSelectorController extends AbstractInachisController
{
    /**
     * Get content list
     * 
     * @param Request $request
     * @param SeriesRepository $seriesRepository
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("/incc/ax/contentSelector/get", methods: [ "POST" ])]
    public function contentList(
        Request $request,
        SeriesRepository $seriesRepository,
        PageRepository $pageRepository,
    ): Response {
        $filters = array_filter($request->request->all('filters'));

        /** @var string $seriesId */
        $seriesId = $request->request->get('seriesId', '');
        if ($seriesId !== '') {
            $series = $seriesRepository->find($seriesId);
            if ($series !== null) {
            $items = $series->getItems();
                if ($items instanceof \Doctrine\Common\Collections\Collection && !$items->isEmpty()) {
                    $filters['excludeIds'] = [];
                    /** @var \Inachis\Entity\Page $item */
                    foreach ($items as $item) {
                        $filters['excludeIds'][] = $item->getId();
                    }
                }
            }
        }
        $offset = (int) $request->request->get('offset', 0);
        $limit = (int) $request->request->get('limit', 25);
        $this->data['pages'] = $pageRepository->getFilteredOfTypeByPostDate(
            $filters,
            '*',
            $offset,
            $limit,
            'title asc'
        );
        $this->data['query'] = [
            'filters' => $filters,
            'offset' => $offset,
            'limit' => $limit,
        ];
        return $this->render('inadmin/dialog/content-selector.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param SeriesRepository $seriesRepository
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("/incc/ax/contentSelector/save", methods: [ "POST" ])]
    public function saveContent(
        Request $request,
        SeriesRepository $seriesRepository,
        PageRepository $pageRepository,
    ): Response {
        $ids = $request->request->all('ids');
        $seriesId = (string) $request->request->get('seriesId', '');
        $series = $seriesRepository->find($seriesId);
        if (empty($ids) || $series === null) {
            return new Response('No change', Response::HTTP_NO_CONTENT);
        }

        foreach ($ids as $pageId) {
            $page = $pageRepository->find($pageId);
            if (!$page instanceof \Inachis\Entity\Page) {
                continue;
            }

            $series->addItem($page);

            $pageDate = $page->getPostDate();
            if ($pageDate instanceof DateTimeInterface) {
                $firstDate = $series->getFirstDate();
                $lastDate = $series->getLastDate();

                if ($firstDate === null || $pageDate < $firstDate) {
                    $series->setFirstDate($pageDate);
                }
                if ($lastDate === null || $pageDate > $lastDate) {
                    $series->setLastDate($pageDate);
                }
            }
        }
        $series->setModDate(new DateTimeImmutable());

        $this->entityManager->persist($series);
        $this->entityManager->flush();

        return new Response('Saved', Response::HTTP_CREATED);
    }
}
