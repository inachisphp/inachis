<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Dashboard;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Page;
use Inachis\Repository\PageRepository;
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
        PageRepository $pageRepository,
    ): Response {
        $this->data['page'] = [
            'tab'   => 'dashboard',
            'title' => 'Dashboard',
        ];
        $this->data['dashboard'] = [
            'draftCount' => 0,
            'publishCount' => 0,
            'upcomingCount' => 0,
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
            )
        ];
        $this->data['dashboard']['stats']['recent'] = 0;
        $this->data['dashboard']['draftCount'] = $this->data['dashboard']['drafts']->count();
        $this->data['dashboard']['upcomingCount'] = $this->data['dashboard']['upcoming']->count();
        $this->data['dashboard']['publishCount'] = $this->data['dashboard']['posts']->count();

        return $this->render('inadmin/page/dashboard/dashboard.html.twig', $this->data);
    }
}
