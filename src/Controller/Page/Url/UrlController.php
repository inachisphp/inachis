<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Url;

use Doctrine\ORM\OptimisticLockException;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Url;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Service\Url\UrlBulkActionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UrlController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param UrlBulkActionService $urlBulkActionService
     * @param UrlRepository $urlRepository
     * @return Response
     * @throws OptimisticLockException
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
        CategoryRepository $categoryRepository,
        ContentQueryParameters $contentQueryParameters,
        UrlBulkActionService $urlBulkActionService,
        UrlRepository $urlRepository,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !empty($request->request->all('items'))) {
            /** @var list<string> */
            $items = $request->request->all('items');
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('make_default') ? 'make_default' : null);

            if ($action !== null) {
                $count = $urlBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count urls.");
            }
            return $this->redirectToRoute('incc_url_list');
        }

        /** @var array{filters: array{keyword?: string}|array{}, sort: string, offset: int, limit: int} */
        $contentQuery = $contentQueryParameters->process(
            $request,
            $categoryRepository,
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
        $this->setPageProperties(['title' => 'URLs', 'tab' => 'url']);

        return $this->render('inadmin/page/url/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param UrlRepository $urlRepository
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
        $url = $request->request->getString('url');
        $urls = $urlRepository->findSimilarUrlsExcludingId(
            $url,
            $request->request->getString('id')
        );

        if (isset($urls[0])) {
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
