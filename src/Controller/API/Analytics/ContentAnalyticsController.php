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
        $data = $analyticsProvider->getPageStatsOverTime(
            $post,
            $request->query->get('from') ?? (new \DateTimeImmutable('90 days ago')),
            $request->query->get('to') ?? new \DateTimeImmutable()
        );

        $this->data['post'] = $post;
        $this->data['stats'] = [
            'from' => $request->query->get('from') ?? (new \DateTimeImmutable('90 days ago')),
            'to' => $request->query->get('to') ?? new \DateTimeImmutable(),
            'viewsPerDay' => $data,
            'totalViews' => array_sum(array_column($data, 'views')),
            'topReferrers' => $analyticsProvider->getTopReferrersForPage(
				'/' . $post->getUrls()->first()->getLink(),
			),
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
        $data = $analyticsProvider->getSeriesStatsOverTime(
            $series,
            $request->query->get('from') ?? (new \DateTimeImmutable('90 days ago')),
            $request->query->get('to') ?? new \DateTimeImmutable()
        );
        $this->data['post'] = $series;
        $this->data['stats'] = [
            'from' => $request->query->get('from') ?? (new \DateTimeImmutable('90 days ago')),
            'to' => $request->query->get('to') ?? new \DateTimeImmutable(),
            'viewsPerDay' => $data,
            'totalViews' => array_sum(array_column($data, 'views')),
            'topReferrers' => $analyticsProvider->getTopReferrersForPage(
				'/' . $series->getLastDate()->format('Y') . '/' . $series->getUrl()
			),
        ];

        return $this->render('inadmin/partials/analytics.html.twig', $this->data);
    }
}
