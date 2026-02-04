<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis\Controller\Page\Tools
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Page\Export\PageExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageExportController extends AbstractInachisController
{
    #[Route('incc/tools/export/page', name: 'incc_tools_export_page', methods: ['GET', 'POST'])]
    public function export(Request $request, PageExportService $pageExportService): Response
    {
        if ($request->isMethod('POST')) {
            $format = $request->request->get('format') ?? 'json';
            $all = $request->request->getBoolean('all', false);
            $selectedIds = $request->request->all('selected') ?? [];

            if ($all) {
                $pages = $pageExportService->getAllPages();
            } else {
                $pages = $pageExportService->getPagesByIds($selectedIds);
            }

            try {
                $exportedData = $pageExportService->export($pages, $format);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('incc_tools_export_page');
            }

            $filename = 'pages_export_' . date('Ymd_His') . '.' . $format;
            $response = new Response($exportedData);
            $response->headers->set('Content-Type', $format === 'json' ? 'application/json' : 'application/xml');
            $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

            return $response;
        }

        $this->data['page']['title'] = 'Export Pages and Posts';
        $this->data['page']['tab'] = 'tools';
        $pages = $pageExportService->getAllPages();
        return $this->render('inadmin/page/tools/export.html.twig', [
            'pages' => $pages,
        ] + $this->data);
    }
}
