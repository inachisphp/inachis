<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis\Controller\Page\Tools
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\PageRepository;
use Inachis\Service\Page\Export\PageExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for exporting pages and posts
 */
#[IsGranted('ROLE_ADMIN')]
class PageExportController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageExportService $pageExportService
     * @return Response
     */
    #[Route('incc/post/export', name: 'incc_post_export', methods: ['GET', 'POST'])]
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
        $pages = [];

        if ($scope === 'manual') {
            $pagesPreview = $pageRepository->getFilteredOfTypeByPostDate(
                array_filter($filter),
                '*',
                0,
                50,
            );
        } elseif ($scope === 'filtered') {
            $pagesPreview = $pageRepository->getFilteredOfTypeByPostDate(
                array_filter($filter),
                $filterType,
                0,
                10000,
            );
        }

        if ($request->isMethod('POST') && $request->request->has('preview')) {
            $previewCount = $scope === 'all'
                ? $pageExportService->getAllCount()
                : count($pagesPreview ?? $selectedIds);
        }

        if ($request->isMethod('POST') && $request->request->has('export')) {
            if ($scope === 'all') {
                $pages = $pageExportService->getAllPages();
            } elseif ($scope === 'manual') {
                if (empty($selectedIds)) {
                    $this->addFlash('error', 'No pages selected for export.');
                    return $this->redirectToRoute('incc_post_export');
                }
                $pages = $pageExportService->getPagesByIds($selectedIds);
            }  elseif ($scope === 'filtered') {
                $pages = $pagesPreview;
            }

            try {
                $exportedData = $pageExportService->export($pages, $format);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('incc_post_export');
            }

            $filename = 'pages-export-' . date('Y-m-d') . '.' . $format;
            if ($format === 'md') {
                return new Response($exportedContent, 200, [
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

        $this->data['page']['title'] = 'Export Pages and Posts';
        $this->data['page']['tab'] = 'tools';
        $this->data['pages'] = $pageExportService->getAllPages();
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

        return $this->render('inadmin/page/post/export.html.twig', $this->data);
    }
}
