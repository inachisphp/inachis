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
use Inachis\Entity\Content\Series;
use Inachis\Entity\Media\Image;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\SeriesType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\Series\SeriesBulkActionService;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Waste\WasteManagerService;
use Inachis\Service\Formatting\UrlNormaliser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param SeriesRepository $seriesRepository
     * @return Response
     */
    #[Route(
        "/incp/series/list/{limit}/{offset}",
        name: 'incp_series_list',
        requirements: [
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "limit" => 10, "offset" => 0, ],
        methods: [ "GET", "POST" ]
    )]
    #[RequiresPermission(
        resource: PermissionResource::SERIES,
        action: PermissionAction::VIEW
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        SeriesBulkActionService $seriesBulkActionService,
        SeriesRepository $seriesRepository,
        ViewStateManager $viewStateManager,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            /** @var list<string> */
            $items = $request->request->all('items');
            $action = $request->request->has('delete') ? 'delete' :
                ($request->request->has('private') ? 'private' :
                    ($request->request->has('public') ? 'public' : null));
            if ($action !== null) {
                $count = $seriesBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count series.");
            }
            return $this->redirectToRoute('incp_series_list');
        }

        $params = $viewStateManager->load(
            $request,
            'series',
            new ViewStateDefaults(
                sort: 'lastDate desc',
                view: 'list',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                'series',
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incp_series_list', [
                'limit' => $request->attributes->getInt('limit'),
                'offset' => 0,
            ]);
        }

        $this->viewModel->page->title = 'Series';
        $this->viewModel->page->tab = 'series';
        return $this->render('inadmin/page/series/list.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'dataset' => $seriesRepository->getFiltered(
                $params->getFilters(),
                $params->getLimit(),
                $params->getOffset(),
                $params->getSort(),
            ),
            'query' => $params,
        ]);
    }

    /**
     * Create/Edit Series
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incp/series/edit/{id}", name: "incp_series_edit", methods: [ "GET", "POST" ])]
    #[Route("/incp/series/new", name: "incp_series_new", methods: [ "GET", "POST" ])]
    #[RequiresPermission(
        resource: PermissionResource::SERIES,
        action: PermissionAction::VIEW
    )]
    public function edit(
        Request $request,
        SeriesRepository $seriesRepository,
        PageRepository $pageRepository,
        WasteManagerService $wasteManagerService,
    ): Response {
        $series = $request->attributes->getString('id', '') !== ''
            ? $seriesRepository->findOneBy([
                'id' => $request->attributes->getString('id'),
            ]) ?? new Series()
            : new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                dump($error->getOrigin()->getName(), $error->getMessage());
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $delete = $form->has('delete') ? $form->get('delete') : null;
            $remove = $form->has('remove') ? $form->get('remove') : null;

            if ($delete instanceof \Symfony\Component\Form\ClickableInterface && $delete->isClicked()) {
                $wasteManagerService->sendToWaste($series);
                $seriesRepository->remove($series);
                return $this->redirect($this->generateUrl('incp_series_list'));
            }
            if (empty($request->request->all('series')['url'])) {
                $series->setUrl(
                    UrlNormaliser::toUri($series->getTitle() ?? '')
                );
            }
            if ($remove instanceof \Symfony\Component\Form\ClickableInterface && $remove->isClicked()) {
                $deleteItems = $pageRepository->findBy([
                    'id' => $request->request->all('series')['itemList']
                ]);
                foreach ($deleteItems as $deleteItem) {
                    $series->getItems()->removeElement($deleteItem);
                }
                if ($series->getItems()->isEmpty()) {
                    $series->setFirstDate(null)->setLastDate(null);
                }
            }

            $series->setAuthor($this->getCurrentUser());
            $series->setUpdatedAt(new DateTimeImmutable());
            $this->entityManager->persist($series);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            return $this->redirect(
                '/incp/series/edit/' .
                $series->getId() . '/'
            );
        }

        $this->viewModel->page->title = $series->getId() !== null ? 'Editing "' . $series->getTitle() . '"' : 'New Series';
        $this->viewModel->page->tab = 'series';
        return $this->render('inadmin/page/series/edit.html.twig', [
            'viewModel' => $this->viewModel,
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'form' => $form->createView(),
            'includeEditor' => true,
            'includeEditorId' => $series->getId()?->toString() ?: '',
            'series' => $series,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incp/series/contents/{id}", name: "incp_series_contents", methods: [ "POST" ])]
    #[RequiresPermission(
        resource: PermissionResource::SERIES,
        action: PermissionAction::VIEW
    )]
    public function contents(Request $request, SeriesRepository $seriesRepository): Response
    {
        $series = $seriesRepository->findOneBy(['id' => $request->attributes->getString('id')]);
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        return $this->render('inadmin/partials/series_contents.html.twig', [
            'form' => $form->createView(),
            'series' => $series,
        ]);
    }
}
