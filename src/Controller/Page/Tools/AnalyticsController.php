<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AnalyticsController extends AbstractInachisController
{
    /**
     * Analytics dashboard for showing general site traffic, popular pages, and 404 errors
     *
     * @param AnalyticsProviderInterface $analytics
     * @return Response
     */
    #[Route('/incc/tools/analytics', name: 'incc_tools_analytics')]
    public function index(AnalyticsProviderInterface $analytics): Response
    {
        $to = new \DateTimeImmutable();
        $from = $to->modify('-30 days');

        $viewsPerDay = $analytics->getPageViewsPerDay($from, $to);
        $topPages = $analytics->getTopPages(10);
        $top404s = $analytics->getTopErrors(10);
        $totalViews = $analytics->getTotalViews($from, $to);
		$uniqueVisitors = $analytics->getMonthlyUniqueVisitors($from, $to);

		$prevFrom = $from->modify('-30 days');
		$prevTo = $from;

		$prevViews = $analytics->getTotalViews($prevFrom, $prevTo);

		$change = $prevViews > 0
			? (($totalViews - $prevViews) / $prevViews) * 100
			: null;


		$this->data['page']['title'] = 'Analytics';
        $this->data['page']['tab'] = 'tools';
		$this->data['analytics'] = [
            'viewsPerDay' => $viewsPerDay,
            'topPages' => $topPages,
            'top404s' => $top404s,
            'totalViews' => $totalViews,
			'uniqueVisitors' => $uniqueVisitors,
            'from' => $from,
            'to' => $to,
			'change' => $change,
		];
        return $this->render('inadmin/page/tools/analytics.html.twig', $this->data);
    }
}