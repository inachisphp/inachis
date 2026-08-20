<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Search;

use Inachis\Repository\Content\SearchRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\UrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchAPIController extends AbstractController
{
    /**
     * JSON search endpoint for front-end clients.
     */
    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchRepository $searchRepository,
        UrlRepository $urlRepository,
        SeriesRepository $seriesRepository,
    ): JsonResponse {
        $keyword = trim((string) $request->query->get('q', ''));

        if ('' === $keyword) {
            return $this->json([
                'query' => $keyword,
                'results' => [],
                'total' => 0,
            ]);
        }

        $results = $searchRepository->searchPublic($keyword, 10);
        $items = [];

        foreach ($results->getResults() as $result) {
            /** @var array<string, mixed> $result */
            $type = is_scalar($result['type'] ?? null) ? strtolower((string) $result['type']) : '';
            $id = is_scalar($result['id'] ?? null) ? (string) $result['id'] : '';
            $title = is_scalar($result['title'] ?? null) ? (string) $result['title'] : '';
            $excerpt = is_scalar($result['content'] ?? null) ? (string) $result['content'] : '';

            if ('series' === $type) {
                $entity = $seriesRepository->find($id);
                $path = null !== $entity && is_scalar($entity->getUrl())
                    ? '/series/'.ltrim((string) $entity->getUrl(), '/')
                    : null;
            } else {
                /** @var \Inachis\Entity\Content\Url|null $url */
                $url = $urlRepository->findOneBy([
                    'content' => $id,
                    'default' => true,
                ]);
                $path = $url instanceof \Inachis\Entity\Content\Url
                    ? '/'.ltrim((string) $url->getLink(), '/')
                    : null;
            }

            $items[] = [
                'id' => $id,
                'entity' => 'series' === $type ? 'series' : 'page',
                'type' => 'series' === $type ? 'series' : $type,
                'title' => $title,
                'excerpt' => $excerpt,
                'url' => $path,
            ];
        }

        return $this->json([
            'query' => $keyword,
            'results' => $items,
            'total' => count($items),
        ]);
    }
}
