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
use Inachis\Entity\Content\Series;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\{PageRepository, SeriesRepository};
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractInachisController
{
    /**
     * Provides the main dashboard
     * 
     * @param Request $request The request made to the controller
     * @return Response
     */
    #[Route('/incc', name: "incc_dashboard", methods: [ 'GET' ])]
    public function default(
        Request $request,
        AnalyticsProviderInterface $analytics,
        ImageRepository $imageRepository,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository
    ): Response {
        $this->viewModel->page->title = 'Dashboard';
        $this->viewModel->page->tab = 'dashboard';

        $recentDraft = $pageRepository->getAll(
            0,
            1,
            [
                'q.status = :status',
                [
                    'status' => EditorialStatus::DRAFT->value,
                ],
            ],
            [ ['q.modDate' , 'DESC'] ]
        );
        if ($recentDraft->count() > 0) {
            $now = new DateTimeImmutable();
            $recentDraftTimeAgo = $now->diff($recentDraft->getIterator()->current()->getModDate());
        }
            
        $drafts = $pageRepository->getAll(
            0,
            5,
            [
                'q.status = :status',
                [
                    'status' => EditorialStatus::DRAFT->value,
                ],
            ],
            'q.postDate ASC, q.modDate'
        );
        $upcoming = $pageRepository->getAll(
            0,
            5,
            [
                'q.status = :status AND q.postDate > :postDate',
                [
                    'status' => EditorialStatus::PUBLISHED->value,
                    'postDate' => new DateTimeImmutable('now')->format('Y-m-d H:i:s'),
                ],
            ],
            'q.postDate ASC, q.modDate'
        );
    
        $posts = $pageRepository->getAll(
            0,
            5,
            [
                'q.status = :status AND q.postDate <= :postDate',
                [
                    'status' => EditorialStatus::PUBLISHED->value,
                    'postDate' => new DateTimeImmutable('now')->format('Y-m-d H:i:s'),
                ],
            ],
            'q.postDate DESC, q.modDate'
        );

        $draftSeries = $seriesRepository->getAll(
            0,
            5,
            [
                'q.visible = :visible',
                [
                    'visible' => false,
                ],
            ],
            'q.firstDate DESC, q.lastDate'
        );
        $series = $seriesRepository->getAll(
            0,
            5,
            [
                'q.visible != :visible',
                [
                    'visible' => false,
                ],
            ],
            'q.firstDate DESC, q.lastDate'
        );


        return $this->render('inadmin/page/dashboard/dashboard.html.twig', [
            'viewModel' => $this->viewModel,
            'dashboard' => [
                'draftTimeAgo' => $recentDraftTimeAgo ?? 0,
                'recentDraft' => $recentDraft,
                
                'drafts' => $drafts,
                'draftCount' => $drafts->count(),
                'posts' => $posts,
                'publishCount' => $posts->count(),
                'upcoming' => $upcoming,
                'upcomingCount' => $upcoming->count(),

                'draftSeries' => $draftSeries,
                'series' => $series,

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
                    'topPages' => $analytics->getTopPages(5),
                    'viewsToday' => $analytics->getTotalViews(
                        new DateTimeImmutable(),
                        new DateTimeImmutable()
                    ),
                    'viewsYesterday' => $analytics->getTotalViews(
                        new DateTimeImmutable('-1 day'),
                        new DateTimeImmutable('-1 day')
                    ),
                    'viewsThisMonth' => $analytics->getTotalViews(
                        new DateTimeImmutable('first day of this month'),
                        new DateTimeImmutable()
                    ),
                    'viewsLastMonth' => $analytics->getTotalViews(
                        new DateTimeImmutable('first day of last month'),
                        new DateTimeImmutable('last day of last month')
                    ),
                    'uniqueVisitorsThisMonth' => $analytics->getMonthlyUniqueVisitors(
                        new DateTimeImmutable('first day of this month'),
                        new DateTimeImmutable()
                    ),
                    'uniqueVisitorsLastMonth' => $analytics->getMonthlyUniqueVisitors(
                        new DateTimeImmutable('first day of last month'),
                        new DateTimeImmutable('last day of last month')
                    ),
                    // 'pageViewsPerDay' => $analytics->getPageViewsPerDay(
                    //     new DateTimeImmutable('-7 days'),
                    //     new DateTimeImmutable()
                    // ),
                ],                
            ],
        ]);
    }
}
