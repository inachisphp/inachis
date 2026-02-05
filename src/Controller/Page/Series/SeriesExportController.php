<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Series;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\PageRepository;
use Inachis\Service\Page\Export\PageExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for exporting series
 */
#[IsGranted('ROLE_ADMIN')]
class SeriesExportController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageExportService $pageExportService
     * @return Response
     */
    #[Route('incc/series/export', name: 'incc_series_export', methods: ['GET', 'POST'])]
    public function export(
        Request $request,
        PageExportService $pageExportService,
        PageRepository $pageRepository,
    ): Response {
        $scope = $request->request->get('scope') ?? 'all';
        $format = $request->request->get('format') ?? 'json';
        $selectedIds = $request->request->get('selected') ?? [];
        $filter = $request->request->all('filter');
        $filterType = $filter['type'] ?? null;
        $filterStatus = $filter['status'] ?? null;
        $filterStartDate = $filter['start_date'] ?? null;
        $filterEndDate = $filter['end_date'] ?? null;
        $filterKeyword = $filter['keyword'] ?? null;

        $pagesPreview = null;
        $previewCount = null;
        $series = [];

        $this->data['page']['title'] = 'Export Series';
        $this->data['page']['tab'] = 'series';
        // $this->data['series'] = $pageExportService->getAllSeries();
        $this->data['scope'] = $scope;
        $this->data['format'] = $format;
        $this->data['manualPages'] = $pagesPreview ;
        $this->data['selectedIds'] = $selectedIds;
        $this->data['previewCount'] = $previewCount;
        $this->data['filterType'] = $filterType;
        $this->data['filterStatus'] = $filterStatus;
        $this->data['filterStartDate'] = $filterStartDate;
        $this->data['filterEndDate'] = $filterEndDate;
        $this->data['filterKeyword'] = $filterKeyword;
        return $this->render('inadmin/page/series/export.html.twig', $this->data);
    }
}
