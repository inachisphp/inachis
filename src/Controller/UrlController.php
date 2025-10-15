<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

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
        requirements: [ "offset" => "\d+", "limit" => "\d+" ],
        defaults: [ "offset" => 0, "limit" => 20 ],
        methods: [ "GET", "POST" ]
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

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/incc/ax/check-url-usage",
        methods: [ "POST" ]
    )]
    public function checkUrlUsage(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $url = $request->get('url');
        $urls = $this->entityManager->getRepository(Url::class)->findSimilarUrlsExcludingId(
            $url,
            $request->get('id')
        );
        if (!empty($urls)) {
            preg_match('/\-([0-9]+)$/', $urls[0]['link'], $matches);
            if (!isset($matches[1])) {
                $matches = [
                  '-0',
                  '0',
                ];
                $urls[0]['link'] .= '-0';
            }
            $url = str_replace($matches[0], '-' . ++$matches[1], $urls[0]['link']);
        }
        return new Response($url);
    }
}
