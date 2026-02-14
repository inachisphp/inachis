<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Series;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Inachis\Form\SeriesType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\ImageRepository;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Service\Series\SeriesBulkActionService;
use Inachis\Util\UrlNormaliser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SeriesController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param SeriesRepository $seriesRepository
     * @return Response
     */
    #[Route(
        "/incc/series/list/{offset}/{limit}",
        name: 'incc_series_list',
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 10 ],
        methods: [ "GET", "POST" ]
    )]
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        SeriesBulkActionService $seriesBulkActionService,
        SeriesRepository $seriesRepository
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete') ? 'delete' :
                ($request->request->has('private') ? 'private' :
                    ($request->request->has('public') ? 'public' : null));
            if ($action !== null && !empty($items)) {
                $count = $seriesBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count series.");
            }
            return $this->redirectToRoute('incc_series_list');
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $seriesRepository,
            'series',
            'lastDate desc',
        );
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $seriesRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['query'] = $contentQuery;
        $this->data['page']['title'] = 'Series';
        $this->data['page']['tab'] = 'series';
        return $this->render('inadmin/page/series/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/series/edit/{id}", name: "incc_series_edit", methods: [ "GET", "POST" ])]
    #[Route("/incc/series/new", name: "incc_series_new", methods: [ "GET", "POST" ])]
    public function edit(
        Request $request,
        SeriesRepository $seriesRepository,
        ImageRepository $imageRepository,
        PageRepository $pageRepository,
    ): Response {
        $series = $request->attributes->get('id') !== null ?
            $seriesRepository->findOneBy([
                'id' => $request->attributes->get('id')
            ]) :
            new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {//} && $form->isValid()) {
            if ($form->getClickedButton()->getName() === 'delete') {
                $seriesRepository->remove($series);
                return $this->redirect($this->generateUrl('incc_series_list'));
            }
            if (!empty($request->request->all('series')['image'])) {
                $series->setImage(
                    $imageRepository->findOneBy([
                        'id' => $request->request->all('series')['image'],
                    ])
                );
            }
            if (empty($request->request->all('series')['url'])) {
                $series->setUrl(
                    UrlNormaliser::toUri($series->getTitle())
                );
            }
            if ($form->getClickedButton()->getName() === 'remove') {
                $deleteItems = $pageRepository->findBy([
                    'id' => $request->request->all('series')['itemList']
                ]);
                foreach ($deleteItems as $deleteItem) {
                    $series->getItems()->removeElement($deleteItem);
                }
                if (empty($series->getItems())) {
                    $series->setFirstDate(null)->setLastDate(null);
                }
            }

            $series->setAuthor($this->getUser());
            $series->setModDate(new DateTimeImmutable());
            $this->entityManager->persist($series);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            return $this->redirect(
                '/incc/series/edit/' .
                $series->getId() . '/'
            );
        }

        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = $series->getId() !== null ? 'Editing "' . $series->getTitle() . '"' : 'New Series';
        $this->data['page']['tab'] = 'series';
        $this->data['series'] = $series;
        $this->data['includeEditor'] = true;
        $this->data['includeEditorId'] = $series->getId();
        $this->data['allowedTypes'] = Image::ALLOWED_MIME_TYPES;
        return $this->render('inadmin/page/series/edit.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/series/contents/{id}", name: "incc_series_contents", methods: [ "POST" ])]
    public function contents(Request $request, SeriesRepository $seriesRepository): Response
    {
        $series = $seriesRepository->findOneBy(['id' => $request->attributes->get('id')]);
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        $this->data['series'] = $series;
        $this->data['form'] = $form->createView();
        return $this->render('inadmin/partials/series_contents.html.twig', $this->data);
    }
}
