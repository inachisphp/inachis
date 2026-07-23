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
use Inachis\Enum\System\CspDirective;
use Inachis\Repository\System\CspReportRepository;
use Inachis\Repository\System\SettingRepository;
use Inachis\Service\System\Csp\CspHeaderManager;
use Inachis\Service\System\Csp\CspPolicyBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CspReportController extends AbstractInachisController
{
    #[Route('/incp/tools/csp', name:'incp_tools_csp_dashboard')]
    public function dashboard(
        Request $request,
        CspReportRepository $repository
    ): Response
    {
        /** @var array<string,string> */
        $filters = $request->query->all('filter') ?: [];
        $severity = $filters['severity'] ?? '';
        $directive = $filters['directive'] ?? '';
        $host = $request->query->get('host');
        $processed = $filters['processed'] ?? '';

        $this->viewModel->page->title = 'CSP Reporting';
        $this->viewModel->page->tab = 'csp-report';
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
                    host: $host,
                    directive: $directive,
                    includeProcessed: $processed === 'all',
                ),
                'filters' => $filters,
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
        '/incp/tools/csp/{id}',
        name: 'csp_report_show',
        requirements: ['id' => '^(?!suggested-policy|reports|settings|.*/process).*$']
    )]
    public function show(
        CspReport $report
    ): Response {
        $this->viewModel->page->title = 'CSP Report';
        $this->viewModel->page->tab = 'csp-report';
        return $this->render(
            'inadmin/page/tools/csp_detail.html.twig',
            [
                'viewModel' => $this->viewModel,
                'report' => $report,
            ]
        );
    }

    #[Route('/incp/tools/csp/{id}/process',
        name: 'csp_report_process',
        requirements: ['id' => '^(?!suggested-policy|reports|settings).*$']
    )]
    public function processPolicyItem(
        CspReport $report,
        CspReportRepository $repository,
        CspHeaderManager $cspHeaderManager,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('report_'.$report->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        match ($request->request->getString('action')) {
            'approve' => [
                $cspHeaderManager->addReportToPolicy($report),
                $repository->processSimilarReports(
                    $report->getViolatedDirective() ?: '',
                    $report->getBlockedUri() ?: '',
                ),
                $this->addFlash('success', 'Domain added to configuration successfully.'),
            ],
            'reject' => $this->addFlash('info', 'Report marked as ignored.'),
            default => throw new \InvalidArgumentException(),
        };

        $report->setProcessed(true);
        $this->entityManager->flush();
        $cspHeaderManager->invalidateCache();

        return $this->redirectToRoute('incp_tools_csp_dashboard');
    }

    #[Route('/incp/tools/csp/suggested-policy', name: 'incp_tools_csp_suggested_policy')]
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
        $this->viewModel->page->tab = 'csp-policy';
        return $this->render(
            'inadmin/page/tools/csp_suggestion.html.twig',
            [
                'viewModel' => $this->viewModel,
                'policyArray' => $suggestedArray,
                'policyString' => $suggestedString,
            ]
        );
    }

    #[Route('/incp/tools/csp/settings', name: 'incp_tools_csp_settings')]
    public function settings(
        Request $request,
        CspHeaderManager $cspHeaderManager,
        SettingRepository $settingRepository,
    ): Response {
        // 1. Fetch current settings or instantiate defaults
        $cspMode = $settingRepository->getOrCreateSetting('csp_mode', 'off');
        $cspUpgradeInsecure = $settingRepository->getOrCreateSetting('csp_upgrade_insecure', '0');

        $cspPolicy = $settingRepository->getOrCreateSetting('csp_policy_frontend', json_encode([
            'default-src' => ['self'],
            'script-src' => ['self'],
            'style-src' => ['self'],
            'img-src' => ['self', 'data-uri']
        ]) ?: '');

        // 2. Handle Form Processing
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('csp_settings', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $cspMode->setValue($request->request->getString('csp_mode', 'off'));
            $cspUpgradeInsecure->setValue($request->request->getString('csp_upgrade_insecure', '0'));

            // Structure incoming inputs into the target array format
            $rawDirectives = $request->request->all('directives');
            $cleanPolicy = [];
            foreach ($rawDirectives as $directiveName => $sourcesString) {
                if (empty($sourcesString) || !is_string($sourcesString)) {
                    continue;
                }
                // Convert space or comma separated lists into arrays
                $cleanPolicy[$directiveName] = array_filter(
                    array_map('trim', preg_split('/[\s,]+/', $sourcesString) ?: [])
                );
            }
            $cspPolicy->setValue(json_encode($cleanPolicy) ?: '');

            $this->entityManager->flush();

            // Clear the APCu/Filesystem cache instantly
            $cspHeaderManager->invalidateCache();

            $this->addFlash('success', 'CSP settings updated and cache warmed.');
            return $this->redirectToRoute('incp_tools_csp_settings');
        }

        $policyData = json_decode($cspPolicy->getValue() ?? '', true) ?? [];
        $displayDirectives = CspDirective::primary();
        foreach (CspDirective::advanced() as $advancedDirective) {
            if (isset($policyData[$advancedDirective->value])) {
                $displayDirectives[] = $advancedDirective;
            }
        }

        $this->viewModel->page->title = 'CSP Policy';
        $this->viewModel->page->tab = 'csp-policy';
        return $this->render('inadmin/page/tools/csp_settings.html.twig', [
            'viewModel' => $this->viewModel,
            'standard_directives' => $displayDirectives,
            'enum_advanced' => CspDirective::advanced(),
            'mode' => $cspMode->getValue(), // String: 'off', 'report-only', or 'enforce'
            'policy' => json_decode($cspPolicy->getValue() ?? '', true) ?? [],
            'upgrade_insecure' => $cspUpgradeInsecure->getValue() === '1',
        ]);
    }
}
