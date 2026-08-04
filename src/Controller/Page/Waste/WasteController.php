<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Waste;

use Inachis\Controller\AbstractInachisController;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Waste\WasteRepository;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Waste\WasteManagerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WasteController extends AbstractInachisController
{
    #[Route(
        '/incp/waste/{limit}/{offset}',
        requirements: [
            'limit' => "\d+",
            'offset' => "\d+",
        ],
        defaults: [
            'limit' => 10,
            'offset' => 0,
        ],
        methods: ['GET', 'POST'],
        name: 'incp_waste_list',
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
                if (null !== $processItem) {
                    if ('' !== $request->request->getString('delete', '')) {
                        $wasteManagerService->deleteWaste($processItem);
                    } elseif ('' !== $request->request->getString('recover', '')) {
                        $wasteManagerService->restore($processItem);
                    }
                }
            }
            $this->addFlash('success', sprintf(
                '%d item(s) have been %s',
                count($request->request->all('items')),
                '' !== $request->request->getString('recover', '') ? 'recovered' : 'deleted',
            ));

            return $this->redirectToRoute(
                'incp_waste_list',
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

    #[Route(
        '/incp/waste/{id}',
        requirements: [
            'id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        ],
        methods: ['GET'],
        name: 'incp_waste_view',
    )]
    public function view(
        string $id,
        WasteRepository $wasteRepository,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $processItem = $wasteRepository->findOneBy(['id' => $id]);
        if (null === $processItem) {
            throw $this->createNotFoundException('The item does not exist or has been permanently deleted');
        }
        $form = $this->createFormBuilder()->getForm();

        $this->viewModel->page->tab = 'waste';

        return $this->render('inadmin/page/waste/view.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'waste' => $processItem,
            'wasteContent' => json_decode($processItem->getContent() ?? '', true),
        ]);
    }
}
