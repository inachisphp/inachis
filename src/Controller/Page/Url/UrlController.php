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
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Url\UrlBulkActionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        "/incc/url/list/{limit}/{offset}",
        name: "incc_url_list",
        requirements: [ "limit" => "\d+", "offset" => "\d+", ],
        defaults: [ "limit" => 20, "offset" => 0, ],
        methods: [ "GET", "POST" ]
    )]
    #[RequiresPermission(
        resource: PermissionResource::PAGE,
        action: PermissionAction::VIEW
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        UrlBulkActionService $urlBulkActionService,
        UrlRepository $urlRepository,
        ViewStateManager $viewStateManager,
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
        
        $params = $viewStateManager->load(
            $request,
            'url',
            new ViewStateDefaults(
                sort: 'contentDate asc',
                view: 'table',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                'url',
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incc_url_list', [
                'limit' => $request->attributes->getInt('limit'),
                'offset' => 0,
            ]);
        }

        $this->viewModel->page->title = 'URLs';
        $this->viewModel->page->tab = 'url';
        return $this->render('inadmin/page/url/list.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $urlRepository->getFiltered(
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
