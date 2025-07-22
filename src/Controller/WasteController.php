<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Waste;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WasteController extends AbstractController
{
    /**
     * @Route("/incc/waste/{offset}/{limit}",
     *     methods={"GET", "POST"},
     *     requirements={
     *          "offset": "\d+",
     *          "limit"="\d+"
     *     },
     *     defaults={"offset"=0, "limit"=10}
     * )
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->get('items'))) {
            foreach ($request->get('items') as $item) {
                if ($request->get('delete') !== null) {
                    $deleteItem = $this->entityManager->getRepository(Waste::class)->findOneById($item);
                    if ($deleteItem !== null) {
                        $this->entityManager->getRepository(Waste::class)->remove($deleteItem);
                    }
                }
            }
            return $this->redirectToRoute(
                'app_waste_list',
                [],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
        }

        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(Waste::class)->getMaxItemsToShow();
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $this->entityManager->getRepository(Waste::class)->getAll(
            $offset,
            $limit,
            [],
            [
                [ 'q.modDate', 'DESC' ]
            ]
        );
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        return $this->render('inadmin/waste.html.twig', $this->data);
    }
}
