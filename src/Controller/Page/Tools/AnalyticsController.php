<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Analytics\AnalyticsPeriodFactory;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyticsController extends AbstractInachisController
{
    /**
     * Analytics dashboard for showing general site traffic, popular pages, and 404 errors
     *
     * @param AnalyticsProviderInterface $analytics
     * @return Response
     */
    #[Route('/incc/tools/analytics', name: 'incc_tools_analytics')]
    public function index(
        AnalyticsProviderInterface $analytics,
        Request $request,
    ): Response {
        $period = AnalyticsPeriodFactory::fromRequest($request);
        $previous = $period->previous();

        $viewsPerDay = $analytics->getPageViewsPerDay($period->from, $period->to);
        $top404s = $analytics->getTopErrors($period->from, $period->to, 10);
        $totalViews = $analytics->getTotalViews($period->from, $period->to);
        $uniqueVisitors = $analytics->getMonthlyUniqueVisitors($period->from, $period->to);

        $prevViews = $analytics->getTotalViews($previous->from, $previous->to);

		$change = $prevViews > 0
			? (($totalViews - $prevViews) / $prevViews) * 100
			: null;
		$trending = $analytics->getTrendingPages(
            $period->from,
            $period->to,
            $previous->from,
            $previous->to,
            10
        );
        $topReferrers = $analytics->getTopReferrers($period->from, $period->to, 10);

        $topRegions = $analytics->getTopRegions($period->from, $period->to, 10);
        $subscriberStats = $analytics->getSubscriberStatsOverTime($period->from, $period->to);
        $subscribersPerFeed = $analytics->getCurrentSubscribersPerFeed();
        $totalSubscribers = array_sum(array_column($subscribersPerFeed, 'subscribers'));
        $topBots = $analytics->getTopBots($period->from, $period->to, 15);

        $this->viewModel->page->title = 'Analytics';
        $this->viewModel->page->tab = 'tools';
        return $this->render('inadmin/page/tools/analytics.html.twig', [
            'viewModel' => $this->viewModel,
            'analytics' => [
                'period' => $period,
                'viewsPerDay' => $viewsPerDay,
                'top404s' => $top404s,
                'totalViews' => $totalViews,
                'uniqueVisitors' => $uniqueVisitors,
                'change' => $change,
                'trending' => $trending,
                'topReferrers' => $topReferrers,
                'topRegions' => $topRegions,
                'subscriberStats' => $subscriberStats,
                'subscribersPerFeed' => $subscribersPerFeed,
                'totalSubscribers' => $totalSubscribers,
                'topBots' => $topBots,
            ],
        ]);
    }
}
