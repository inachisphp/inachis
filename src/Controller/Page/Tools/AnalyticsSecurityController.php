<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Analytics\AnalyticsPeriodResolver;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Exception\InvalidAnalyticsPeriodException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyticsSecurityController extends AbstractInachisController
{
    /**
     * Analytics dashboard for showing general site traffic, popular pages, and 404 errors
     *
     * @param AnalyticsProviderInterface $analytics
     * @return Response
     */
    #[Route('/incc/tools/analytics/security', name: 'incc_tools_analytics_security')]
    #[Route('/admin/analytics/security', name: 'admin_analytics_security')]
    public function index(
		AnalyticsProviderInterface $analytics,
		AnalyticsPeriodResolver $periodResolver,
        Request $request,
	): Response
    {
		try {
			$period = $periodResolver->resolve(
				$request,
				'analytics-security'
			);
		} catch (InvalidAnalyticsPeriodException $e) {
			$this->addFlash('warning', $e->getMessage());

			return $this->redirectToRoute(
				'incc_tools_analytics_security',
				[
					'range' => '30d',
				]
			);
		}
        // $previous = $period->previous();

        $this->viewModel->page->title = 'Security Events';
        $this->viewModel->page->tab = 'analytics-security';

        return $this->render('inadmin/page/tools/analytics_security.html.twig', [
			'viewModel' => $this->viewModel,

			'security' => [
				'period' => $period,

				'summary' => $analytics->getSecuritySummary(
					$period->from,
					$period->to
				),

				'eventsByType' => $analytics->getSecurityEventsByType(
					$period->from,
					$period->to
				),

				'topPaths' => $analytics->getTopSecurityPaths(
					$period->from,
					$period->to
				),

				'topIps' => $analytics->getTopSecurityIps(
					$period->from,
					$period->to
				),

				'eventsPerDay' => $analytics->getSecurityEventsPerDay(
					$period->from,
					$period->to
				),

				'recentEvents' => $analytics->getRecentSecurityEvents(),
			],
		]);
    }
}
