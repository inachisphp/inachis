<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Analytics;

use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentAnalyticsController extends AbstractInachisController
{
    /**
     *  Gets statistics for the specified post.
     */
    #[Route('/incp/api/stats/post/{id}', name: 'incp_api_post_stats', methods: ['POST'])]
    public function postStats(
        Request $request,
        AnalyticsProviderInterface $analyticsProvider,
        PageRepository $pageRepository,
        string $id,
    ): Response {
        $post = $pageRepository->findOneBy(['id' => $id]);
        if (null == $post) {
            return new Response('');
        }

        $fromDate = $request->query->has('from')
            ? new \DateTimeImmutable($request->query->getString('from'))
            : new \DateTimeImmutable('90 days ago');

        $toDate = $request->query->has('to')
            ? new \DateTimeImmutable($request->query->getString('to'))
            : new \DateTimeImmutable();

        $data = $analyticsProvider->getPageStatsOverTime(
            $post,
            $fromDate,
            $toDate,
        );
        $url = $post->getUrls()->first();
        $topReferrers = [];
        if (false !== $url) {
            $topReferrers = $analyticsProvider->getTopReferrersForPage(
                '/'.$url->getLink(),
            );
        }

        return $this->render('inadmin/partials/analytics.html.twig', [
            'post' => $post,
            'stats' => [
                'from' => $fromDate,
                'to' => $toDate,
                'viewsPerDay' => $data,
                'totalViews' => array_sum(array_column($data, 'views')),
                'topReferrers' => $topReferrers,
            ],
        ]);
    }

    /**
     * Returns statistics for the specified series.
     */
    #[Route('/incp/api/stats/series/{id}', name: 'incp_api_series_stats', methods: ['POST'])]
    public function seriesStats(
        Request $request,
        AnalyticsProviderInterface $analyticsProvider,
        SeriesRepository $seriesRepository,
        string $id,
    ): Response {
        $series = $seriesRepository->findOneBy(['id' => $id]);
        if (null == $series) {
            return new Response('');
        }
        $fromDate = $request->query->has('from')
            ? new \DateTimeImmutable($request->query->getString('from'))
            : new \DateTimeImmutable('90 days ago');

        $toDate = $request->query->has('to')
            ? new \DateTimeImmutable($request->query->getString('to'))
            : new \DateTimeImmutable();
        $data = $analyticsProvider->getSeriesStatsOverTime(
            $series,
            $fromDate,
            $toDate,
        );

        return $this->render('inadmin/partials/analytics.html.twig', [
            'post' => $series,
            'stats' => [
                'from' => $fromDate,
                'to' => $toDate,
                'viewsPerDay' => $data,
                'totalViews' => array_sum(array_column($data, 'views')),
                'topReferrers' => $analyticsProvider->getTopReferrersForPage(
                    '/'.($series->getLastDate()?->format('Y') ?? '').'/'.$series->getUrl(),
                ),
            ],
        ]);
    }
}
