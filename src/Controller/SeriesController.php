<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Form\SeriesType;
use App\Utils\UrlNormaliser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/incc/series/list/{offset}/{limit}",
        methods: [ "GET", "POST" ],
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 10 ]
    )]
    public function list(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->get('items'))) {
            foreach ($request->get('items') as $item) {
                if ($request->get('delete') !== null) {
                    $deleteItem = $this->entityManager->getRepository(Series::class)->findOneById($item);
                    if ($deleteItem !== null) {
                        $this->entityManager->getRepository(Series::class)->remove($deleteItem);
                    }
                }
//                if ($request->get('privacy') !== null) {
//                    $post = $entityManager->getRepository(Series::class)->findOneById($item);
//                    if ($post !== null) {
//                        $post->setVisibility(Page::VIS_PRIVATE);
//                        $post->setModDate(new \DateTime('now'));
//                        $entityManager->persist($post);
//                    }
//                }
            }
//            if ($request->get('privacy') !== null) {
//                $entityManager->flush();
//            }
            return $this->redirectToRoute(
                'app_series_list',
                [],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
        }

        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(Series::class)->getMaxItemsToShow();
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $this->entityManager->getRepository(Series::class)->getAll(
            $offset,
            $limit,
            [],
            [
//                [ 'q.lastDate', 'DESC' ]
                [ 'q.title', 'ASC' ]
            ]
        );
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        return $this->render('inadmin/series__list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/series/edit/{id}", methods: [ "GET", "POST" ])]
    #[Route("/incc/series/new", methods: [ "GET", "POST" ], name: "app_series_new")]
    public function edit(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $series = $request->get('id') !== null ?
            $this->entityManager->getRepository(Series::class)->findOneById($request->get('id')):
            new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            if (!empty($request->get('series')['image'])) {
                $series->setImage(
                    $this->entityManager->getRepository(Image::class)->findOneById(
                        $request->get('series')['image']
                    )
                );
            }
            if (empty($request->get('series')['url'])) {
                $series->setUrl(
                    UrlNormaliser::toUri($series->getTitle())
                );
            }
            if ($form->get('remove')->isClicked()) {
                $deleteItems = $this->entityManager->getRepository(Page::class)->findBy([
                    'id' => $request->get('series')['itemList']
                ]);
                foreach ($deleteItems as $deleteItem) {
                    $series->getItems()->removeElement($deleteItem);
                }
                if (empty($series->getItems())) {
                    $series->setFirstDate(null)->setLastDate(null);
                }
            }
            if ($form->get('delete')->isClicked()) {
                $this->entityManager->getRepository(Series::class)->remove($series);
                return $this->redirect($this->generateUrl('app_series_list'));
            }

            $series->setModDate(new \DateTime('now'));
            $this->entityManager->persist($series);
            $this->entityManager->flush();

            $this->addFlash('notice', 'Content saved.');
            return $this->redirect(
                '/incc/series/edit/' .
                $series->getId() . '/'
            );
        }

        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = $series->getId() !== null ?
            'Editing "' . $series->getTitle() . '"' :
            'New Series';
        $this->data['series'] = $series;
        $this->data['includeEditor'] = true;
        $this->data['includeEditorId'] = $series->getId();
        $this->data['includeDatePicker'] = true;
        return $this->render('inadmin/series__edit.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param int $year
     * @param string $title
     * @return Response
     */
    #[Route("/{year}-{title}", methods: [ "GET" ])]
    public function view(Request $request, int $year, string $title): Response
    {
        $this->data['series'] = $this->entityManager->getRepository(Series::class)->getSeriesByYearAndUrl(
            $year,
            $title
        );
        return $this->render('web/series.html.twig', $this->data);
    }
}
