<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Search;

use Inachis\Controller\AbstractWebController;
use Inachis\Repository\Content\SearchRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\UrlRepository;
use Ramsey\Uuid\Uuid;
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
        $keyword = trim($request->query->getString('q', ''));
        $pageNumber = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $offset = ($pageNumber - 1) * $limit;

        $results = [];
        $total = 0;
        if ('' !== $keyword) {
            $searchResults = $searchRepository->searchPublic($keyword, $limit, $offset);
            $total = $searchResults->getTotal();

            foreach ($searchResults->getResults() as $result) {
                /** @var array<string, mixed> $result */
                $type = is_scalar($result['type'] ?? null) ? strtolower((string) $result['type']) : '';
                $uuidString = Uuid::fromBytes($result['id'])->toString();
                $title = is_scalar($result['title'] ?? null) ? (string) $result['title'] : '';
                $excerpt = is_scalar($result['content'] ?? null) ? (string) $result['content'] : '';

                if ('series' === $type) {
                    $entity = $seriesRepository->find($uuidString);
                    $url = null !== $entity && is_scalar($entity->getUrl())
                        ? '/series/'.ltrim((string) $entity->getUrl(), '/')
                        : null;
                } else {
                    /** @var \Inachis\Entity\Content\Url|null $contentUrl */
                    $contentUrl = $urlRepository->findOneBy([
                        'content' => $uuidString,
                        'default' => true,
                    ]);
                    $url = $contentUrl instanceof \Inachis\Entity\Content\Url
                        ? '/'.ltrim((string) $contentUrl->getLink(), '/')
                        : null;
                }

                $results[] = [
                    'id' => $uuidString,
                    'title' => $title,
                    'type' => 'series' === $type ? 'series' : $type,
                    'excerpt' => $excerpt,
                    'url' => $url,
                ];
            }
        }

        $this->viewModel->page->title = '' === $keyword ? 'Search' : sprintf('Search results for “%s”', $keyword);

        return $this->render('web/pages/search.html.twig', [
            'viewModel' => $this->viewModel,
            'keyword' => $keyword,
            'pageNumber' => $pageNumber,
            'perPage' => $limit,
            'results' => $results,
            'total' => $total,
            'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 1,
        ]);
    }
}
