<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Export\Category\CategoryExportService;
use Inachis\Service\Export\Page\PageExportService;
use Inachis\Service\Export\Series\SeriesExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractInachisController
{
    #[Route('/incp/tools/export', name: 'incp_tools_export', methods: ['GET', 'POST'])]
    public function export(
        Request $request,
        CategoryExportService $categoryExportService,
        PageExportService $pageExportService,
        SeriesExportService $seriesExportService,
    ): Response {
        $this->viewModel->page->title = 'Export Data';
        $this->viewModel->page->tab = 'export';

        if ($request->isMethod('POST')) {
            $contentType = $request->request->get('content_type', 'post');
            $format = strtolower($request->request->get('format', 'json'));
            $scope = $request->request->get('scope', 'all');
            $selectedIds = array_filter(explode(',', (string) $request->request->get('selectedIds', '')));
            $includeLinkedPages = (bool) $request->request->get('include_linked_pages', false);

            // Normalize content_type ('post' -> 'page')
            $domain = 'post' === $contentType ? 'page' : $contentType;

            try {
                $exportData = match ($domain) {
                    'category' => $categoryExportService->export(null, $format),
                    'series' => $seriesExportService->export(
                        'manual' === $scope && !empty($selectedIds)
                            ? $seriesExportService->getSeriesByIds($selectedIds)
                            : null,
                        $format,
                        $includeLinkedPages
                    ),
                    default => $pageExportService->export(
                        'manual' === $scope && !empty($selectedIds)
                            ? $pageExportService->getPagesByIds($selectedIds)
                            : ('filtered' === $scope
                                ? $pageExportService->getFilteredPages((array) $request->request->all('filter'))
                                : null),
                        $format
                    ),
                };
            } catch (\InvalidArgumentException|\RuntimeException $e) {
                $this->addFlash('error', 'Export failed: ' . $e->getMessage());
                return $this->redirectToRoute('incp_tools_export');
            }

            $filename = sprintf('export-%s-%s.%s', $domain, date('Y-m-d-His'), $format);
            $response = new Response($exportData);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set(
                'Content-Type',
                'xml' === $format ? 'application/xml' : ('json' === $format ? 'application/json' : 'text/markdown')
            );

            return $response;
        }

        return $this->render('inadmin/page/tools/export.html.twig', [
            'viewModel' => $this->viewModel,
        ]);
    }
}
