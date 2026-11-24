<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ExportAjaxController extends AbstractInachisController
{
    #[Route('/incp/tools/export/ajax', name: 'incp_tools_export_ajax', methods: ['GET'])]
    public function fetchSelectionList(
        Request $request,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository,
        CategoryRepository $categoryRepository,
    ): JsonResponse {
        $domain = $request->query->get('domain', 'post');
        $keyword = trim((string) $request->query->get('keyword', ''));

        $items = match ($domain) {
            'series' => array_map(static fn($s) => [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'subtitle' => $s->getSubTitle() ?? '',
            ], $seriesRepository->findByKeyword($keyword)),

            'category' => array_map(static fn($c) => [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'subtitle' => $c->getFullPath(),
            ], $categoryRepository->findByKeyword($keyword)),

            default => array_map(static fn($p) => [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'subtitle' => $p->getType() . ' | ' . $p->getStatus()->value,
            ], $pageRepository->findByKeyword($keyword)),
        };

        return new JsonResponse(['items' => $items]);
    }
}