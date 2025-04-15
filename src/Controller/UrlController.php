<?php

namespace App\Controller;

use App\Entity\Url;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UrlController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/incc/url/list/{offset}/{limit}",
        methods: [ "GET", "POST" ],
        defaults: [ "offset" => 0, "limit" => 20 ],
        requirements: [ "offset" => "\d+", "limit" => "\d+" ]
    )]
    public function list(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->get('items'))) {
            foreach ($request->get('items') as $item) {
                $link = $this->entityManager->getRepository(Url::class)->findOneBy(
                    [
                        'id' =>$item,
                        'default' => false,
                    ]
                );
                if ($link !== null) {
                    if ($request->request->has('delete')) {
                        $this->entityManager->getRepository(Url::class)->remove($link);
                    }
                    if ($request->request->has('make_default')) {
                        $previous_default = $this->entityManager->getRepository(Url::class)->findOneBy(
                            [
                                'content' => $link->getContent(),
                                'default' => true,
                            ]
                        );
                        if ($previous_default !== null) {
                            $previous_default->setDefault(false)->setModDate(new \DateTime('now'));
                            $this->entityManager->persist($previous_default);
                        }
                        $link->setDefault(true)->setModDate(new \DateTime('now'));
                        $this->entityManager->persist($link);
                        $this->entityManager->flush();
                    }
                }
            }
            return $this->redirectToRoute('app_url_list');
        }
        $filters = array_filter($request->get('filter', []));
        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(Url::class)->getMaxItemsToShow();
        $this->data['dataset'] = $this->entityManager->getRepository(Url::class)->getAll(
            $offset,
            $limit,
            [],
            [
                [ 'substring(q.link, 1, 10)', 'asc' ],
                [ 'q.default', 'desc' ],
                [ 'q.createDate', 'desc' ],
            ]
        );
        $this->data['form'] = $form->createView();
        $this->data['filters'] = $filters;
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['title'] = 'URLs';

        return $this->render('inadmin/url__list.html.twig', $this->data);
    }
}
