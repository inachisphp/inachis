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
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            foreach ($request->request->all('items') as $item) {
                if ($request->request->get('delete') !== null) {
                    $deleteItem = $wasteRepository->findOneBy(['id' => $item]);
                    if ($deleteItem !== null) {
                        $wasteRepository->remove($deleteItem);
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
}
