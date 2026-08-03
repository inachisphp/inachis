<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Analytics\AnalyticsPeriodResolver;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Exception\InvalidAnalyticsPeriodException;
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
    #[Route('/incp/tools/analytics', name: 'incp_tools_analytics')]
    public function index(
        AnalyticsProviderInterface $analytics,
        AnalyticsPeriodResolver $periodResolver,
        Request $request,
    ): Response {
        try {
            $period = $periodResolver->resolve(
                $request,
                'analytics'
            );
        } catch (InvalidAnalyticsPeriodException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute(
                'incp_tools_analytics',
                [
                    'range' => '30d',
                ]
            );
        }
        $previous = $period->previous();

        $viewsPerDay = $analytics->getPageViewsPerDay($period->from, $period->to);
        $top404s = $analytics->getTopErrors($period->from, $period->to, 10);
        $totalViews = $analytics->getTotalViews($period->from, $period->to);
        $uniqueVisitors = $analytics->getMonthlyUniqueVisitors($period->from, $period->to);

        $total404s = $analytics->getTotalErrors($period->from, $period->to);
        $previous404s = $analytics->getTotalErrors($previous->from, $previous->to);

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
        $this->viewModel->page->tab = 'analytics';
        return $this->render('inadmin/page/tools/analytics.html.twig', [
            'viewModel' => $this->viewModel,
            'analytics' => [
                'period' => $period,

                'totalViews' => $totalViews,
                'previousTotalViews' => $prevViews,
                'averageViewsPerDay' => round(
                    $totalViews / max(
                        1,
                        $period->from->diff($period->to)->days + 1
                    ),
                    1
                ),
                'peakViews' => array_reduce(
                    $viewsPerDay,
                    static function ($carry, $row) {
                        return ($carry === null || $row['total'] > $carry['total'])
                            ? $row
                            : $carry;
                    }
                ),
                'uniqueVisitors' => $uniqueVisitors,
                'viewsPerVisitor' => $uniqueVisitors > 0
                    ? round($totalViews / $uniqueVisitors, 2)
                    : 0,
                'totalSubscribers' => $totalSubscribers,
                'total404s' => $total404s,
                'previous404s' => $previous404s,
                'errorChange' => $previous404s > 0
                    ? (($total404s - $previous404s) / $previous404s) * 100
                    : null,

                'viewsPerDay' => $viewsPerDay,
                'top404s' => $top404s,
                'change' => $change,
                'trending' => $trending,
                'topReferrers' => $topReferrers,
                'topRegions' => $topRegions,
                'subscriberStats' => $subscriberStats,
                'subscribersPerFeed' => $subscribersPerFeed,
                'topBots' => $topBots,
            ],
        ]);
    }
}
