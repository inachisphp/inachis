<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Entity\Page;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTime;

class ContentSelectorController extends AbstractInachisController
{
    protected array $errors = [];
    protected array $data = [];

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/contentSelector/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $filters = array_filter($request->get('filters', []));
        if ($request->get('seriesId', '') !== '') {
            $series = $this->entityManager->getRepository(Series::class)->find($request->get('seriesId'));
            if ($series !== null && !$series->getItems()->isEmpty()) {
                $filters['excludeIds'] = [];
                foreach ($series->getItems() as $item) {
                    $filters['excludeIds'][] = $item->getId();
                }
            }
        }
        $offset = (int) $request->get('offset', 0);
        $limit = (int) $request->get('limit', 25);
        $this->data['pages'] = $this->entityManager->getRepository(Page::class)->getFilteredOfTypeByPostDate(
            $filters,
            '*',
            $offset,
            $limit,
            'title asc'
        );
        $this->data['filters'] = $filters;
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        return $this->render('inadmin/dialog/content-selector.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/contentSelector/save", methods: [ "POST" ])]
    public function saveContent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!empty($request->get('ids'))) {
            $series = $this->entityManager->getRepository(Series::class)->findOneById($request->get('seriesId'));
            if ($series !== null) {
                foreach ($request->get('ids') as $pageId) {
                    $page = $this->entityManager->getRepository(Page::class)->findOneById($pageId);
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
