<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\API\Analytics;

use Inachis\Controller\AbstractInachisController;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Repository\Content\{PageRepository, SeriesRepository};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ContentAnalyticsController extends AbstractInachisController
{
    /**
     *  Gets statistics for the specified post
     * 
     * @param Request $request
     * @param AnalyticsProviderInterface $analyticsProvider
     * @param PageRepository $pageRepository
     * @param string $id
     * @return Response
     */
    #[Route("/incc/api/stats/post/{id}", name: "incc_api_post_stats", methods: [ "POST" ])]
    public function postStats(
        Request $request,
        AnalyticsProviderInterface $analyticsProvider,
        PageRepository $pageRepository,
        string $id,
    ): Response
    {
        $post = $pageRepository->findOneBy(['id' => $id]);
        if ($post == null) {
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
        if ($url !== false) {
            $topReferrers = $analyticsProvider->getTopReferrersForPage(
                '/' . $url->getLink()
            );
        }

        $this->data['post'] = $post;
        $this->data['stats'] = [
            'from' => $fromDate,
            'to' => $toDate,
            'viewsPerDay' => $data,
            'totalViews' => array_sum(array_column($data, 'views')),
            'topReferrers' => $topReferrers,
        ];

        return $this->render('inadmin/partials/analytics.html.twig', $this->data);
    }

    /**
     * Returns statistics for the specified series
     * 
     * @param Request $request
     * @param AnalyticsProviderInterface $analyticsProvider
     * @param SeriesRepository $seriesRepository
     * @param string $id
     * @return Response
     */
    #[Route("/incc/api/stats/series/{id}", name: "incc_api_series_stats", methods: [ "POST" ])]
    public function seriesStats(
        Request $request,
        AnalyticsProviderInterface $analyticsProvider,
        SeriesRepository $seriesRepository,
        string $id,
    ): Response
    {
        $series = $seriesRepository->findOneBy(['id' => $id]);
        if ($series == null) {
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
        $this->data['post'] = $series;
        $this->data['stats'] = [
            'from' => $fromDate,
            'to' => $toDate,
            'viewsPerDay' => $data,
            'totalViews' => array_sum(array_column($data, 'views')),
            'topReferrers' => $analyticsProvider->getTopReferrersForPage(
				'/' . ($series->getLastDate()?->format('Y') ?? '') . '/' . $series->getUrl()
			),
        ];

        return $this->render('inadmin/partials/analytics.html.twig', $this->data);
    }
}
