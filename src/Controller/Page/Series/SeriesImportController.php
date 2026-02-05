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

class SeriesImportController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageExportService $pageExportService
     * @return Response
     */
    #[Route('incc/series/import', name: 'incc_series_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        PageExportService $pageExportService,
        PageRepository $pageRepository,
    ): Response {
        return $this->render('inadmin/page/series/import.html.twig', $this->data);
    }
}
