<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Waste;

use Inachis\Controller\AbstractInachisController;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Waste\WasteRepository;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Waste\WasteManagerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class WasteController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param WasteRepository $wasteRepository
     * @return Response
     */
    #[Route(
        "/incc/waste/{limit}/{offset}",
        requirements: [
          "limit" => "\d+",
          "offset" => "\d+",
        ],
        defaults: [
            "limit" => 10,
            "offset" => 0,
        ],
        methods: [ 'GET', 'POST' ],
        name: "incc_waste_list"
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        WasteRepository $wasteRepository,
        WasteManagerService $wasteManagerService,
        ViewStateManager $viewStateManager,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            foreach ($request->request->all('items') as $item) {
                $processItem = $wasteRepository->findOneBy(['id' => $item]);
                if ($processItem !== null) {
                    if ($request->request->getString('delete', '') !== '') {
                        $wasteManagerService->deleteWaste($processItem);
                    } elseif ($request->request->getString('recover', '') !== '') {
                        $wasteManagerService->restore($processItem);
                    }
                }
            }
            $this->addFlash('success', sprintf(
                '%d item(s) have been %s',
                count($request->request->all('items')),
                $request->request->getString('recover', '') !== '' ? 'recovered' : 'deleted',
            ));
            return $this->redirectToRoute(
                'incc_waste_list',
                [],
            );
        }

        $params = $viewStateManager->build(
            $request,
            'waste',
            new ViewStateDefaults(
                sort: 'updatedAt desc',
                view: 'list',
            ),
            $categoryRepository,
        );

        $this->viewModel->page->tab = 'waste';
        return $this->render('inadmin/page/waste/list.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $wasteRepository->getFiltered(
                $params->getFilters(),
                $params->getLimit(),
                $params->getOffset(),
                $params->getSort(),
            ),
            'form' => $form->createView(),
            'query' => $params,
        ]);
    }

    /**
     * @param string $id
     * @param WasteRepository $wasteRepository
     * @return Response
     */
    #[Route(
        "/incc/waste/{id}",
        requirements: [
            "id" => "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"
        ],
        methods: [ 'GET' ],
        name: "incc_waste_view"
    )]
    public function view(
        string $id,
        WasteRepository $wasteRepository,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $processItem = $wasteRepository->findOneBy(['id' => $id]);
        if ($processItem === null) {
            throw $this->createNotFoundException('The item does not exist or has been permanently deleted');
        }
        $form = $this->createFormBuilder()->getForm();

        $this->viewModel->page->tab = 'waste';
        return $this->render('inadmin/page/waste/view.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'waste' => $processItem,
            'wasteContent' =>json_decode($processItem->getContent() ?? '', true),
        ]);
    }
}
