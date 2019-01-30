<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractInachisController
{
    const ITEMS_TO_SHOW = 10;

    /**
     * @Route("/", methods={"GET"})
     */
    public function homepage()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $series = $entityManager->getRepository(Series::class)->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [
                'q.lastDate < :postDate',
                [
                    'postDate' => new \DateTime(),
                ],
            ],
            [
                [ 'q.lastDate', 'DESC' ]
            ]
        );

        $this->data['content'] = [];
        $excludePages = [];
        if (!empty($series)) {
            foreach ($series as $group) {
                if (!empty($group->getItems())) {
                    foreach ($group->getItems() as $page) {
                        $excludePages[] = $page->getId();
                    }
                }
                $this->data['content'][$group->getLastDate()->format('Ymd')] = $group;
            }
            unset($series);
        }

        $pages = $entityManager->getRepository(Page::class)->getAll(
            0,
            self::ITEMS_TO_SHOW,
            [
                'q.status = :status AND q.postDate <= :postDate AND q.id NOT IN (:excludedPages)',
                [
                    'status'   => Page::PUBLISHED,
                    'postDate' => new \DateTime(),
                    'excludedPages' => $excludePages,
                ],
            ],
            'q.postDate DESC, q.modDate'
        );
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $this->data['content'][$page->getPostDate()->format('Ymd')] = $page;
            }
            unset($pages);
            krsort($this->data['content']);
        }

        return $this->render('web/homepage.html.twig', $this->data);
    }
}
