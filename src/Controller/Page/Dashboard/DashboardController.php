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
use Inachis\Entity\{Page, Series};
use Inachis\Repository\{PageRepository, SeriesRepository};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractInachisController
{
    /**
     * @param Request $request The request made to the controller
     * @return Response
     */
    #[Route('/incc', name: "incc_dashboard", methods: [ 'GET' ])]
    public function default(
        Request $request,
        AnalyticsProviderInterface $analytics,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository
    ): Response {
        $this->data['page'] = [
            'tab'   => 'dashboard',
            'title' => 'Dashboard',
        ];
        $recentDraft = $pageRepository->getAll(
            0,
            1,
            [
                'q.status = :status',
                [
                    'status' => Page::DRAFT,
                ],
            ],
            [ ['q.modDate' , 'DESC'] ]
        );
        if ($recentDraft->count() > 0) {
            $now = new DateTimeImmutable();
            $recentDraftTimeAgo = $now->diff($recentDraft->getIterator()->current()->getModDate());
        }
        $this->data['dashboard'] = [
            'draftCount' => 0,
            'publishCount' => 0,
            'upcomingCount' => 0,
            'recentDraft' => $recentDraft,
            'draftTimeAgo' => $recentDraftTimeAgo ?? 0,
            'drafts' => $pageRepository->getAll(
                0,
                5,
                [
                    'q.status = :status',
                    [
                        'status' => Page::DRAFT,
                    ],
                ],
                'q.postDate ASC, q.modDate'
            ),
            'upcoming' => $pageRepository->getAll(
                0,
                5,
                [
                    'q.status = :status AND q.postDate > :postDate',
                    [
                        'status' => Page::PUBLISHED,
                        'postDate' => new DateTimeImmutable(),
                    ],
                ],
                'q.postDate ASC, q.modDate'
            ),
            'posts' => $pageRepository->getAll(
                0,
                5,
                [
                    'q.status = :status AND q.postDate <= :postDate',
                    [
                        'status' => Page::PUBLISHED,
                        'postDate' => new DateTimeImmutable(),
                    ],
                ],
                'q.postDate DESC, q.modDate'
            ),
            'draftSeries' => $seriesRepository->getAll(
                0,
                5,
                [
                    'q.visibility = :visibility',
                    [
                        'visibility' => Series::PRIVATE,
                    ],
                ],
                'q.firstDate DESC, q.lastDate'
            ),
            'series' => $seriesRepository->getAll(
                0,
                5,
                [
                    'q.visibility != :visibility',
                    [
                        'visibility' => Series::PRIVATE,
                    ],
                ],
                'q.firstDate DESC, q.lastDate'
            )
        ];
        $this->data['dashboard']['analytics'] = [
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
        ];
        $this->data['dashboard']['stats']['recent'] = 0;
        $this->data['dashboard']['draftCount'] = $this->data['dashboard']['drafts']->count();
        $this->data['dashboard']['upcomingCount'] = $this->data['dashboard']['upcoming']->count();
        $this->data['dashboard']['publishCount'] = $this->data['dashboard']['posts']->count();

        return $this->render('inadmin/page/dashboard/dashboard.html.twig', $this->data);
    }
}
