<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Url;

use App\Controller\AbstractInachisController;
use App\Entity\Url;
use App\Model\ContentQueryParameters;
use App\Repository\UrlRepository;
use App\Service\Url\UrlBulkActionService;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UrlController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/incc/url/list/{offset}/{limit}",
        name: "incc_url_list",
        requirements: [ "offset" => "\d+", "limit" => "\d+" ],
        defaults: [ "offset" => 0, "limit" => 20 ],
        methods: [ "GET", "POST" ]
    )]
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        UrlBulkActionService $urlBulkActionService,
        UrlRepository $urlRepository,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('make_default') ? 'make_default' : null);

            if ($action !== null && !empty($items)) {
                $count = $urlBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count urls.");
            }
            return $this->redirectToRoute('incc_url_list');
        }
        $contentQuery = $contentQueryParameters->process(
            $request,
            $urlRepository,
            'url',
            'contentDate asc',
        );
        $this->data['dataset'] = $urlRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['form'] = $form->createView();
        $this->data['query'] = $contentQuery;
        $this->data['page']['title'] = 'URLs';

        return $this->render('inadmin/page/url/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/incc/ax/check-url-usage",
        methods: [ "POST" ]
    )]
    public function checkUrlUsage(
        Request $request,
        UrlRepository $urlRepository,
    ): Response {
        $url = $request->request->get('url');
        $urls = $urlRepository->findSimilarUrlsExcludingId(
            $url,
            $request->request->get('id')
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
