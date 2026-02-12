<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Diagnostics\DiagnosticsCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DiagnosticsController extends AbstractInachisController
{
    /**
     * Server settings page
     *
     * @param DiagnosticsCollector $collector
     * @return Response
     */
    #[Route('/incc/tools/server', name: 'incc_tools_diagnostics')]
    public function index(DiagnosticsCollector $collector): Response {
        $results = $collector->collect();

        $this->data['page']['title'] = 'Server settings';
        $this->data['page']['tab'] = 'tools';
        $this->data['sections'] = $collector->grouped();
        $this->data['environment'] = $this->getParameter('kernel.environment');
        $this->data['summary'] = $this->buildSummary($results);

		return $this->render('inadmin/page/tools/server.html.twig', $this->data);
	}

    /**
     * Build a summary array for the summary cards in Twig
     *
     * @param array $results Array of CheckResult objects
     * @return array
     */
    private function buildSummary(array $results): array
    {
        $summary = [
            'database' => ['ok' => 0, 'warning' => 0, 'error' => 0],
            'environment' => ['ok' => 0, 'warning' => 0, 'error' => 0],
            'performance' => ['ok' => 0, 'warning' => 0, 'error' => 0],
            'security' => ['ok' => 0, 'warning' => 0, 'error' => 0],
            'webserver' => ['ok' => 0, 'warning' => 0, 'error' => 0],
        ];

        foreach ($results as $result) {
            $sectionKey = match ($result->section) {
                'Database' => 'database',
                'Environment' => 'environment',
                'Performance' => 'performance',
                'Security' => 'security',
                'Webserver' => 'webserver',
                default => null,
            };

            if ($sectionKey) {
                $status = $result->status;
                if (!isset($summary[$sectionKey][$status])) {
                    $summary[$sectionKey][$status] = 0;
                }
                $summary[$sectionKey][$status]++;
            }
        }

        return $summary;
    }

    /**
     * Output server settings to JSON file
     *
     * @param DiagnosticsCollector $collector
     * @return JsonResponse
     */
    #[Route('/incc/tools/server.json', name: 'incc_tools_diagnostics_json')]
    public function serverJson(DiagnosticsCollector $collector): JsonResponse {
        return $this->json([
            'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'results' => $collector->collect(),
        ]);
	}
}