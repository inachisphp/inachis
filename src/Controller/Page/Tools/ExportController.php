<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Export\Category\CategoryExportService;
use Inachis\Service\Export\Page\PageExportService;
use Inachis\Service\Export\Series\SeriesExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for exporting pages and posts.
 */
class ExportController extends AbstractInachisController
{
    #[Route('incp/tools/export', name: 'incp_tools_export', methods: ['GET', 'POST'])]
    public function export(
        Request $request,
        CategoryExportService $categoryExportService,
        PageExportService $pageExportService,
        PageRepository $pageRepository,
        SeriesExportService $seriesExportService,
    ): Response {
        $contentType = $request->request->getString('content_type', 'post');
        $scope = $request->request->getString('scope', 'all');
        $format = $request->request->getString('format', 'json');

        $rawSelectedIds = $request->request->get('selectedIds');
        $selectedIdsString = is_string($rawSelectedIds) ? $rawSelectedIds : '';
        /** @var list<string> $selectedIds */
        $selectedIds = array_values(array_filter(
            explode(',', $selectedIdsString),
            static fn (string $id): bool => '' !== trim($id),
        ));

        /** @var array<string, mixed> $filter */
        $filter = $request->request->all('filter');
        $filterType = $filter['type'] ?? null;
        $filterStatus = $filter['status'] ?? null;
        $filterStartDate = $filter['start_date'] ?? null;
        $filterEndDate = $filter['end_date'] ?? null;
        $filterKeyword = $filter['keyword'] ?? null;

        $pagesPreview = null;
        $previewCount = null;

        if ($request->isMethod('POST') && $request->request->has('export')) {
            /** @var CategoryExportService|PageExportService|SeriesExportService|null $exportService */
            $exportService = null;
            /** @var iterable<object> $items */
            $items = [];

            switch ($contentType) {
                case 'category':
                    $items = $categoryExportService->getAllCategories();
                    $exportService = $categoryExportService;
                    break;

                case 'post':
                    if ('all' === $scope) {
                        $items = $pageExportService->getAllPages();
                    } elseif ('manual' === $scope) {
                        if (empty($selectedIds)) {
                            $this->addFlash('error', 'No pages selected for export.');

                            return $this->redirectToRoute('incp_tools_export');
                        }
                        $items = $pageExportService->getPagesByIds($selectedIds);
                    } elseif ('filtered' === $scope) {
                        /** @var array{type?: string, categories?: array<string>, tags?: array<string>, status?: string, visible?: bool, keyword?: string, excludeIds?: list<string>} $typedFilter */
                        $typedFilter = $filter;
                        $items = $pageExportService->getFilteredPages($typedFilter);
                    }
                    $exportService = $pageExportService;
                    break;

                case 'series':
                    if ('all' === $scope) {
                        $items = $seriesExportService->getAllSeries();
                    } elseif ('manual' === $scope) {
                        if (empty($selectedIds)) {
                            $this->addFlash('error', 'No series selected for export.');

                            return $this->redirectToRoute('incp_tools_export');
                        }
                        $items = $seriesExportService->getSeriesByIds($selectedIds);
                    } elseif ('filtered' === $scope) {
                        /** @var array{categories?: array<string>, tags?: array<string>, status?: string, visible?: bool, visibility?: bool, issues?: string, keyword?: string, excludeIds?: list<string>} $typedFilter */
                        $typedFilter = array_filter($filter);
                        $items = $pageRepository->getFilteredOfTypeByPostDate(
                            $typedFilter,
                            '*',
                            10000,
                            0,
                        );
                    }
                    $exportService = $seriesExportService;
                    break;
            }

            if (null === $exportService) {
                $this->addFlash('error', 'Invalid content type selected for export.');

                return $this->redirectToRoute('incp_tools_export');
            }

            try {
                $exportedData = match (true) {
                    $exportService instanceof CategoryExportService => (function () use ($exportService, $items, $format): string {
                        /** @var iterable<Category> $categories */
                        $categories = $items;

                        return $exportService->export($categories, $format);
                    })(),
                    $exportService instanceof PageExportService => (function () use ($exportService, $items, $format): string {
                        /** @var iterable<Page> $pages */
                        $pages = $items;

                        return $exportService->export($pages, $format);
                    })(),
                    $exportService instanceof SeriesExportService => (function () use ($exportService, $items, $format): string {
                        /** @var iterable<Series> $series */
                        $series = $items;

                        return $exportService->export($series, $format);
                    })(),
                };
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('incp_tools_export');
            }

            $filename = $contentType.'-export-'.date('Y-m-d-His').'.'.$format;
            if ('md' === $format) {
                return new Response($exportedData, 200, [
                    'Content-Type' => 'text/markdown',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }

            $responseContentType = 'json' === $format ? 'application/json' : 'application/xml';

            return new Response($exportedData, 200, [
                'Content-Type' => $responseContentType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $this->viewModel->page->title = 'Export';
        $this->viewModel->page->tab = 'export';

        return $this->render('inadmin/page/tools/export.html.twig', [
            'viewModel' => $this->viewModel,
            'pages' => $pageExportService->getAllPages(),
            'scope' => $scope,
            'format' => $format,
            'contentType' => $contentType,
            'manualPages' => $pagesPreview,
            'selectedIds' => $selectedIds,
            'previewCount' => $previewCount,
            'filterType' => $filterType,
            'filterStatus' => $filterStatus,
            'filterStartDate' => $filterStartDate,
            'filterEndDate' => $filterEndDate,
            'filterKeyword' => $filterKeyword,
        ]);
    }

    #[Route('incp/ax/tools/export', name: 'incp_tools_export_ajax', methods: ['GET'])]
    public function exportAjax(
        Request $request,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository,
    ): Response {
        $contentType = $request->query->get('content_type', 'post');
        $query = (string) $request->query->get('q', '');
        $page = (int) $request->query->get('page', 1);

        $selectedIdsString = (string) $request->query->get('selectedIds', '');
        /** @var list<string> $selectedIds */
        $selectedIds = array_values(array_filter(
            explode(',', $selectedIdsString),
            static fn (string $id): bool => '' !== trim($id),
        ));

        if ('' === trim($query)) {
            return new Response('', 200);
        }

        $limit = 25;
        $offset = ($page - 1) * $limit;
        $items = [];
        $total = 0;

        switch ($contentType) {
            case 'post':
                $items = $pageRepository->getFilteredOfTypeByPostDate(
                    ['keyword' => $query],
                    '*',
                    $limit,
                    $offset,
                    'parent_id',
                );
                break;

            case 'series':
                $items = $seriesRepository->getFiltered(
                    ['keyword' => $query],
                    $limit,
                    $offset,
                );
                break;
        }

        return $this->render('inadmin/partials/export_table.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $items,
            'content_type' => $contentType,
            'form' => $this->createFormBuilder()->getForm()->createView(),
            'pagination' => [
                'offset' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
            'selectedIds' => $selectedIds,
        ]);
    }
}
