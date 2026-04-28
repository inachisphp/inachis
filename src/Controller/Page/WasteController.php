<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page;

use Inachis\Controller\AbstractInachisController;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\WasteRepository;
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
        "/incc/waste/{offset}/{limit}",
        requirements: [
          "offset" => "\d+",
          "limit" => "\d+"
        ],
        defaults: [
            "offset" => 0,
            "limit" => 10
        ],
        methods: [ 'GET', 'POST' ],
        name: "incc_waste_list"
    )]
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        WasteRepository $wasteRepository,
        WasteManagerService $wasteManagerService,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            foreach ($request->request->all('items') as $item) {
                $processItem = $wasteRepository->findOneBy(['id' => $item]);
                if ($processItem !== null) {
                    if ($request->request->get('delete') !== null) {
                        $wasteManagerService->deleteWaste($processItem);
                    } elseif ($request->request->get('recover') !== null) {
                        $wasteManagerService->restore($processItem);
                    }
                }
            }
            return $this->redirectToRoute(
                'incc_waste_list',
                [],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $wasteRepository,
            'waste',
            'modDate desc',
        );
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $wasteRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['query'] = $contentQuery;
        $this->data['page']['tab'] = 'waste';
        return $this->render('inadmin/page/waste/list.html.twig', $this->data);
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

        $this->data['waste'] = $processItem;
        $this->data['wasteContent'] = json_decode($processItem->getContent(), true);
        $this->data['page']['tab'] = 'waste';

        $form = $this->createFormBuilder()->getForm();
        $this->data['form'] = $form->createView();

        return $this->render('inadmin/page/waste/view.html.twig', $this->data);
    }
}
