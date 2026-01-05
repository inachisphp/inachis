<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ContentSelectorController extends AbstractInachisController
{
    protected array $errors = [];
    protected array $data = [];

    /**
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
        $filters = array_filter($request->request->all('filters', []));
        if ($request->request->get('seriesId', '') !== '') {
            $series = $seriesRepository->find($request->request->get('seriesId'));
            if ($series !== null && !$series->getItems()->isEmpty()) {
                $filters['excludeIds'] = [];
                foreach ($series->getItems() as $item) {
                    $filters['excludeIds'][] = $item->getId();
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
        $this->data['query']['filters'] = $filters;
        $this->data['query']['offset'] = $offset;
        $this->data['query']['limit'] = $limit;
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
        if (!empty($request->request->all('ids'))) {
            $series = $seriesRepository->findOneBy(['id' => $request->request->get('seriesId')]);
            if ($series !== null) {
                foreach ($request->request->all('ids') as $pageId) {
                    $page = $pageRepository->findOneBy(['id' => $pageId]);
                    if (empty($page) || empty($page->getId())) {
                        continue;
                    }
                    $series->addItem($page);
                    $firstDate = $series->getFirstDate();
                    $lastDate = $series->getLastDate();
                    if ($firstDate === null || $page->getPostDate()->format('Y-m-d H:i:s') < $firstDate->format('Y-m-d H:i:s')) {
                        $series->setFirstDate($page->getPostDate());
                    }
                    if ($lastDate === null || $page->getPostDate()->format('Y-m-d H:i:s') > $lastDate->format('Y-m-d H:i:s')) {
                        $series->setLastDate($page->getPostDate());
                    }
                }
                $series->setModDate(new DateTime('now'));
                $this->entityManager->persist($series);
                $this->entityManager->flush();
                return new Response('Saved', Response::HTTP_CREATED);
            }
        }
        return new Response('No change', Response::HTTP_NO_CONTENT);
    }
}
