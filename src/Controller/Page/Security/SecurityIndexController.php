<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Diagnostics\DiagnosticsCollector;
use Inachis\Repository\Security\SecurityPolicyRepository;
use Inachis\Repository\System\SettingRepository;
use Inachis\Service\System\Domain\DomainEmailAnalyser;
use Inachis\Service\System\Domain\ExternalIpAddress;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityIndexController extends AbstractInachisController
{
    /**
     * List of setting pages.
     */
    #[Route('/incp/security', name: 'incp_security_list')]
    public function index(
        DiagnosticsCollector $diagnosticsCollector,
        DomainEmailAnalyser $domainEmailAnalyser,
        ExternalIpAddress $externalIpAddress,
        SecurityPolicyRepository $securityPolicyRepository,
        SettingRepository $settingRepository,
    ): Response {
        $results = $diagnosticsCollector->collect();
        $systemIssues = [
            'warning' => [],
            'error' => [],
        ];
        foreach ($results as $result) {
            if ('warning' === $result->status || 'error' === $result->status) {
                $systemIssues[$result->status][] = $result;
            }
        }
        $domain = isset($_ENV['APP_DOMAIN']) && is_string($_ENV['APP_DOMAIN']) ?
            $_ENV['APP_DOMAIN'] :
            'example.com';
        $serverIp = !empty($_ENV['SERVER_IP']) && is_string($_ENV['SERVER_IP']) ?
            $_ENV['SERVER_IP'] :
            $externalIpAddress->getExternalIp();
        $selector = isset($_ENV['DKIM_SELECTOR']) && is_string($_ENV['DKIM_SELECTOR']) ?
            $_ENV['DKIM_SELECTOR'] :
            'default';
        $report = $domainEmailAnalyser->analyse($domain, $serverIp, $selector);

        $this->viewModel->page->title = 'Security & Privacy';
        $this->viewModel->page->tab = 'security';

        return $this->render('inadmin/page/security/list.html.twig', [
            'viewModel' => $this->viewModel,
            'cspMode' => $settingRepository->getOrCreateSetting('csp_mode', 'off'),
            'domainEmailReport' => $report,
            'securityPolicy' => $securityPolicyRepository->findActive(),
            'systemIssues' => $systemIssues,
        ]);
    }
}
