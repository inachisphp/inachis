<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Dashboard;

use DateTimeImmutable;
use Inachis\Analytics\AnalyticsProviderInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\{PageRepository, SeriesRepository};
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractInachisController
{
    /**
     * Provides the main dashboard
     *
     * @return Response
     */
    #[Route('/incc', name: "incc_dashboard", methods: [ 'GET' ])]
    public function default(
        AnalyticsProviderInterface $analytics,
        ImageRepository $imageRepository,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository
    ): Response {
        $this->viewModel->page->title = 'Dashboard';
        $this->viewModel->page->tab = 'dashboard';

        $recentDraft = $pageRepository->findMostRecentlyEditedDraft();
        if ($recentDraft) {
            $now = new DateTimeImmutable();
            $recentDraftTimeAgo = $now->diff($recentDraft->getUpdatedAt());
        }

        $draftPosts = $pageRepository->findRecentDrafts(5);
        $upcoming = $pageRepository->findUpcoming(5);
        $recentPosts = $pageRepository->findRecentPublished(5);
        $counts = $pageRepository->getDashboardCounts();

        $draftSeries = $seriesRepository->findRecentDrafts(5);
        $recentSeries = $seriesRepository->findRecentPublished(5);
        $analyticsSummary = $analytics->getDashboardSummary();


        return $this->render('inadmin/page/dashboard/dashboard.html.twig', [
            'viewModel' => $this->viewModel,
            'dashboard' => [
                'draftTimeAgo' => $recentDraftTimeAgo ?? 0,
                'recentDraft' => $recentDraft,

                'drafts' => $draftPosts,
                'draftCount' => $counts['drafts'],
                'posts' => $recentPosts,
                'publishCount' => $counts['published'],
                'upcoming' => $upcoming,
                'upcomingCount' => $counts['upcoming'],

                'draftSeries' => $draftSeries,
                'series' => $recentSeries,

                'alerts' => [
                    'altText' => [
                        'count' => $imageRepository->getImagesWithoutAltTextCount(),
                        // 'pages' => $imageRepository->getImagesWithoutAltText(5),
                    ],
                    'tags' => [
                        'count' => $pageRepository->getPagesWithoutTagsCount(),
                        // 'pages' => $pageRepository->getPagesWithoutTags(5),
                    ],
                    'categories' => [
                        'count' => $pageRepository->getPagesWithoutCategoriesCount(),
                        // 'pages' => $pageRepository->getPagesWithoutCategories(5),
                    ],
                    'featureImage' => [
                        'count' => $pageRepository->getPagesWithoutFeatureImageCount(),
                        // 'pages' => $pageRepository->getPagesWithoutFeatureImage(5),
                    ],
                    'sharingMessage' => [
                        'count' => $pageRepository->getPagesWithoutSharingMessageCount(),
                        // 'pages' => $pageRepository->getPagesWithoutSharingMessage(5),
                    ],
                ],
                'analytics' => [
                    ...$analyticsSummary,
                    'topPages' => $analytics->getTopPages(5),
                ],
            ],
        ]);
    }
}
