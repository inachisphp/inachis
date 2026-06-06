<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Search;

use Inachis\Repository\SearchRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\UrlRepository;
use Inachis\Controller\AbstractWebController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchWebController extends AbstractWebController
{
    /**
     * Front-end search page.
     */
    #[Route('/search', name: 'web_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchRepository $searchRepository,
        UrlRepository $urlRepository,
        SeriesRepository $seriesRepository,
    ): Response {
        $this->setDefaults();

        $keyword = trim((string) $request->query->get('q', ''));
        $pageNumber = max(1, (int) $request->query->get('page', 1));
        $limit = 25;
        $offset = ($pageNumber - 1) * $limit;

        $results = [];
        $total = 0;
        if ($keyword !== '') {
            $searchResults = $searchRepository->searchPublic($keyword, $offset, $limit);
            $total = $searchResults->getTotal();

            foreach ($searchResults->getResults() as $result) {
                /** @var array<string, mixed> $result */
                $type = is_scalar($result['type'] ?? null) ? strtolower((string) $result['type']) : '';
                $id = is_scalar($result['id'] ?? null) ? (string) $result['id'] : '';
                $title = is_scalar($result['title'] ?? null) ? (string) $result['title'] : '';
                $excerpt = is_scalar($result['content'] ?? null) ? (string) $result['content'] : '';

                if ($type === 'series') {
                    $entity = $seriesRepository->find($id);
                    $url = $entity !== null && is_scalar($entity->getUrl())
                        ? '/series/' . ltrim((string) $entity->getUrl(), '/')
                        : null;
                } else {
                    /** @var \Inachis\Entity\Content\Url|null $contentUrl */
                    $contentUrl = $urlRepository->findOneBy([
                        'content' => $id,
                        'default' => true,
                    ]);
                    $url = $contentUrl instanceof \Inachis\Entity\Content\Url
                        ? '/' . ltrim((string) $contentUrl->getLink(), '/')
                        : null;
                }

                $results[] = [
                    'id' => $id,
                    'title' => $title,
                    'type' => $type === 'series' ? 'series' : $type,
                    'excerpt' => $excerpt,
                    'url' => $url,
                ];
            }
        }

        $data = (array) ($this->data ?? []);
        $pageMeta = (array) ($data['page'] ?? []);
        $pageMeta['title'] = $keyword === '' ? 'Search' : sprintf('Search results for “%s”', $keyword);

        $data['page'] = $pageMeta;
        $data['keyword'] = $keyword;
        $this->data = $data;
        $this->data['results'] = $results;
        $this->data['total'] = $total;
        $this->data['pageNumber'] = $pageNumber;
        $this->data['perPage'] = $limit;
        $this->data['totalPages'] = $total > 0 ? (int) ceil($total / $limit) : 1;

        return $this->render('web/pages/_search.html.twig', $this->data);
    }
}
