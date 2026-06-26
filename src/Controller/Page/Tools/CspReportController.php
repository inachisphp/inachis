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
use Inachis\Service\System\Csp\CspPolicyBuilder;
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

        $this->viewModel->page->title = 'CSP Reporting';
        $this->viewModel->page->tab = 'tools';
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

    /**
     * Show the contents of the CSP report
     * 
     * @param CspReport $report
     * @return Response
     */
    #[Route(
        '/incc/tools/csp/{id}', 
        name: 'csp_report_show', 
        requirements: ['id' => '^(?!suggested-policy|reports).*$']
    )]
    public function show(
        CspReport $report
    ): Response {

        $this->viewModel->page->title = 'CSP Report';
        $this->viewModel->page->tab = 'tools';
        return $this->render(
            'inadmin/page/tools/csp_detail.html.twig',
            [
                'viewModel' => $this->viewModel,
                'report' => $report,
            ]
        );
    }

    #[Route('/incc/tools/csp/suggested-policy', name: 'incc_tools_csp_suggested_policy')]
    public function suggestedPolicy(
        CspReportRepository $repository,
        CspPolicyBuilder $policyBuilder
    ): Response {
        // 1. Grab lightweight scalar entries from DB
        $rawReports = $repository->findUniqueDirectivesAndBlockedUris();

        // 2. Aggregate and normalize into structural directives
        $suggestedArray = $policyBuilder->buildPolicyFromReports($rawReports);
        $suggestedString = $policyBuilder->stringifyPolicy($suggestedArray);


        $this->viewModel->page->title = 'CSP Policy Suggestion';
        $this->viewModel->page->tab = 'tools';
        return $this->render(
            'inadmin/page/tools/csp_suggestion.html.twig',
            [
                'viewModel' => $this->viewModel,
                'policyArray' => $suggestedArray,
                'policyString' => $suggestedString,
            ]
        );
    }
}
