<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Search;

use App\Controller\AbstractInachisController;
use App\Repository\SearchRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class SearchController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route("/incc/search/results/{keyword}/{offset}/{limit}",
        name: "incc_search_results",
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 25 ],
        methods: [ "GET", "POST" ],
    )]
    public function results(SearchRepository $repo, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $results = $repo->search(
            $request->attributes->get('keyword'),
            $request->attributes->get('offset'),
            $request->attributes->get('limit')
        );

        $this->data['form'] = $form->createView();
//        $this->data['page']['sort'] = $sort;
        $this->data['page']['offset'] = $results->getOffset();
        $this->data['page']['limit'] = $results->getLimit();
        $this->data['page']['title'] =  sprintf('\'%s\' results', $request->attributes->get('keyword'));

        $this->data['results'] = $results;
        $this->data['total'] = $results->getTotal();
        $this->data['keyword'] = $request->attributes->get('keyword');

//        $keyword = $request->query->get('q', '');
//        $page = max(1, (int) $request->query->get('page', 1));
//        $sort = $request->query->get('sort', 'title');
//        $dir = $request->query->get('dir', 'ASC');

        return $this->render('inadmin/page/search/results.html.twig', $this->data);
    }
}