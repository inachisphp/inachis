<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Service\Export\Category\CategoryExportService;
use Inachis\Service\Page\Export\PageExportService;
use Inachis\Service\Series\Export\SeriesExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for exporting pages and posts
 */
#[IsGranted('ROLE_ADMIN')]
class ExportController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageExportService $pageExportService
     * @return Response
     */
    #[Route('incc/tools/export', name: 'incc_tools_export', methods: ['GET', 'POST'])]
    public function export(
        Request $request,
        CategoryExportService $categoryExportService,
        PageExportService $pageExportService,
        SeriesExportService $seriesExportService,
    ): Response {
        $contentType = $request->request->get('content_type') ?? 'post';
        $scope = $request->request->get('scope') ?? 'all';
        $format = $request->request->get('format') ?? 'json';
        $selectedIds = array_filter(explode(',', $request->request->get('selectedIds') ?? ''));
        $filter = $request->request->all('filter');
        $filterType = $filter['type'] ?? null;
        $filterStatus = $filter['status'] ?? null;
        $filterStartDate = $filter['start_date'] ?? null;
        $filterEndDate = $filter['end_date'] ?? null;
        $filterKeyword = $filter['keyword'] ?? null;

        $pagesPreview = null;
        $previewCount = null;
        $pages = [];

        if ($request->isMethod('POST') && $request->request->has('export')) {
            switch ($contentType) {
                case 'category':
                    $items = $categoryExportService->getAllCategories();
                    $exportService = $categoryExportService;
                    break;

                case 'post':
                    if ($scope === 'all') {
                        $items = $pageExportService->getAllPages();
                    } elseif ($scope === 'manual') {
                        if (empty($selectedIds)) {
                            $this->addFlash('error', 'No pages selected for export.');
                            return $this->redirectToRoute('incc_tools_export');
                        }
                        $items = $pageExportService->getPagesByIds($selectedIds);
                    }  elseif ($scope === 'filtered') {
                        $items = $pageExportService->getFilteredPages($filter);
                    }
                    $exportService = $pageExportService;
                    break;

                case 'series':
                    if ($scope === 'all') {
                        $items = $seriesExportService->getAllSeries();
                    } elseif ($scope === 'manual') {
                        if (empty($selectedIds)) {
                            $this->addFlash('error', 'No series selected for export.');
                            return $this->redirectToRoute('incc_tools_export');
                        }
                        $items = $seriesExportService->getSeriesByIds($selectedIds);
                    }  elseif ($scope === 'filtered') {
                        $items = $pageRepository->getFilteredOfTypeByPostDate(
                            array_filter($filter),
                            '*',
                            0,
                            10000,
                        );
                    }
                    $exportService = $seriesExportService;
                    break;
            }

            try {
                $exportedData = $exportService->export($items, $format);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('incc_tools_export');
            }

            $filename = $contentType . '-export-' . date('Y-m-d-His') . '.' . $format;
            if ($format === 'md') {
                return new Response($exportedData, 200, [
                    'Content-Type' => 'text/markdown',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }

            $contentType = $format === 'json' ? 'application/json' : 'application/xml';
            return new Response($exportedData, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $this->data['page']['title'] = 'Export';
        $this->data['page']['tab'] = 'export';
        $this->data['pages'] = $pageExportService->getAllPages();
        $this->data['scope'] = $scope;
        $this->data['format'] = $format;
        $this->data['contentType'] = $contentType;
        $this->data['manualPages'] = $pagesPreview ;
        $this->data['selectedIds'] = $selectedIds;
        $this->data['previewCount'] = $previewCount;
        $this->data['filterType'] = $filterType;
        $this->data['filterStatus'] = $filterStatus;
        $this->data['filterStartDate'] = $filterStartDate;
        $this->data['filterEndDate'] = $filterEndDate;
        $this->data['filterKeyword'] = $filterKeyword;

        return $this->render('inadmin/page/tools/export.html.twig', $this->data);
    }

    #[Route('incc/ax/tools/export', name: 'incc_tools_export_ajax', methods: ['GET'])]
    public function exportAjax(
        Request $request,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository,
    ): Response {
        $contentType = $request->query->get('content_type', 'post');
        $query = $request->query->get('q', '');
        $page = (int) $request->query->get('page', 1);
        $selectedIds = array_filter(explode(',', $request->query->get('selectedIds', '')));

        if (empty(trim($query))) {
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
                    $offset,
                    $limit,
                    'parent_id',
                );
                //$total = get count - make sure results limited to 50
                break;

            case 'series':
                $items = $seriesRepository->getFiltered(
                    ['keyword' => $query],
                    $offset,
                    $limit
                );
                //$total = get count - make sure results limited to 50
                break;

            default:
                $pages = [];
                break;
        }

        // $total = $pageExportService->getFilteredOfTypeByPostDateCount(
        //     ['keyword' => $query],
        //     $contentType
        // );

        $this->data['form'] = $this->createFormBuilder()->getForm()->createView();
        $this->data['dataset'] = $items;
        $this->data['content_type'] = $contentType;
        $this->data['pagination'] = [
            'offset' => $page,
            'total' => $total,
            'limit' => $limit,
        ];
        $this->data['selectedIds'] = $selectedIds;
        return $this->render('inadmin/partials/export_table.html.twig', $this->data);
    }
}
