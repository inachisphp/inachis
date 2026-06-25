<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\System\CspReport;
use Inachis\Repository\System\CspReportRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CspReportController extends AbstractInachisController
{
    #[Route('/incc/tools/csp', name:'incc_tools_csp_dashboard')]
    public function dashboard(
        Request $request,
        CspReportRepository $repository
    ): Response
    {
        $severity = $request->query->get('severity');
        $host = $request->query->get('host');

        return $this->render(
            'inadmin/page/tools/csp_dashboard.html.twig',
            [
                'viewModel' => $this->viewModel,
                'today' => $repository->countToday(),
                'critical' => $repository->countCritical(),
                'hosts' => $repository->countUniqueHosts(),
                'directives' => $repository->countUniqueDirectives(),
                'topHosts' => $repository->findTopHosts(),
                'topDirectives' => $repository->findTopDirectives(),
                'topCritical' => $repository->findTopCriticalGrouped(),
                'reports' => $repository->findFiltered(
                    severity: $severity,
                    host: $host
                ),
                'activeSeverity' => $severity,
                'activeHost' => $host,
            ]
        );
    }

    #[Route('/incc/tools/csp/{id}', name: 'csp_report_show')]
    public function show(
        CspReport $report
    ): Response {
        return $this->render(
            'inadmin/page/tools/csp_detail.html.twig',
            [
                'viewModel' => $this->viewModel,
                'report' => $report,
            ]
        );
    }

    #[Route('/incc/tools/csp/reports/filter', name: 'csp_reports_filter')]
    public function filter(
        Request $request,
        CspReportRepository $repository
    ): Response {
        $severity = $request->query->get('severity');
        $host = $request->query->get('host');

        return $this->render(
            'inadmin/page/tools/csp_reports.html.twig',
            [
                'viewModel' => $this->viewModel,
                'reports' => $repository->findFiltered(
                    severity: $severity,
                    host: $host
                ),
            ]
        );
    }
}
