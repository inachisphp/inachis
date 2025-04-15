<?php

namespace App\Controller;

use App\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractInachisController
{
    /**
     * @param Request             $request    The request made to the controller
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/incc', methods: [ 'GET' ])]
    public function default(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->data['page'] = [
            'tab'   => 'dashboard',
            'title' => 'Dashboard',
        ];
        $this->data['dashboard'] = [
            'draftCount'    => 0,
            'publishCount'  => 0,
            'upcomingCount' => 0,
            'drafts'        => $this->entityManager->getRepository(Page::class)->getAll(
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
            'upcoming' => $this->entityManager->getRepository(Page::class)->getAll(
                0,
                5,
                [
                    'q.status = :status AND q.postDate > :postDate',
                    [
                        'status'   => Page::PUBLISHED,
                        'postDate' => new \DateTime(),
                    ],
                ],
                'q.postDate ASC, q.modDate'
            ),
            'posts' => $this->entityManager->getRepository(Page::class)->getAll(
                0,
                5,
                [
                    'q.status = :status AND q.postDate <= :postDate',
                    [
                        'status'   => Page::PUBLISHED,
                        'postDate' => new \DateTime(),
                    ],
                ],
                'q.postDate DESC, q.modDate'
            )
        ];
        $this->data['dashboard']['stats']['recent'] = 0;
        $this->data['dashboard']['draftCount'] = $this->data['dashboard']['drafts']->count();
        $this->data['dashboard']['upcomingCount'] = $this->data['dashboard']['upcoming']->count();
        $this->data['dashboard']['publishCount'] = $this->data['dashboard']['posts']->count();
        return $this->render('inadmin/dashboard.html.twig', $this->data);
    }
}
