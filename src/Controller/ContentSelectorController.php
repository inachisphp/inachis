<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentSelectorController extends AbstractInachisController
{
    protected $errors = [];
    protected $data = [];

    /**
     * @param Request $request
     */
    #[Route("/incc/ax/contentSelector/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // @todo paginate data returned with auto-loading from Ajax
        $this->data['pages'] = $this->entityManager->getRepository(Page::class)->findBy([], [
            'title' => 'ASC'
        ]);
        return $this->render('inadmin/dialog/content-selector.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/contentSelector/save", methods: [ "POST" ])]
    public function saveContent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!empty($request->get('ids'))) {
            $series = $this->entityManager->getRepository(Series::class)->findOneById($request->get('seriesId'));
            foreach ($request->get('ids') as $pageId) {
                $page = $this->entityManager->getRepository(Page::class)->findOneById($pageId);
                if (!empty($page) && !empty($page->getId())) {
                    $series->addItem($page);
                }
                $firstDate = $series->getFirstDate();
                $lastDate = $series->getLastDate();
                if ($firstDate === null || $page->getPostDate()->format('Y-m-d H:i:s') < $firstDate->format('Y-m-d H:i:s')) {
                    $series->setFirstDate($page->getPostDate());
                }
                if ($lastDate === null || $page->getPostDate()->format('Y-m-d H:i:s') > $lastDate->format('Y-m-d H:i:s')) {
                    $series->setLastDate($page->getPostDate());
                }
            }
            $series->setModDate(new \DateTime('now'));
            $this->entityManager->getManager()->persist($series);
            $this->entityManager->getManager()->flush();
            return new Response('Saved', Response::HTTP_CREATED);
        }
        return new Response('No change', Response::HTTP_NO_CONTENT);
    }
}
