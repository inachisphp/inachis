<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Search;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Url;
use Inachis\Entity\User;
use Inachis\Repository\SearchRepository;
use Inachis\Repository\UrlRepository;
use Inachis\Repository\UserRepository;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SearchController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    #[Route("/incc/search/results/{keyword}/{offset}/{limit}",
        name: "incc_search_results",
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "keyword" => null, "offset" => 0, "limit" => 25 ],
        methods: [ "GET", "POST" ],
    )]
    public function results(
        Request $request,
        SearchRepository $searchRepository,
        UrlRepository $urlRepository,
        UserRepository $userRepository,
    ): Response {
        if ($request->attributes->get('keyword') === ' ' && !empty($request->request->get('keyword', ''))) {
            $keyword = str_replace('/', '', $request->request->get('keyword', ''));
            $keyword = preg_replace('/(?:%25)*2[fF]/', '', $keyword);
            return $this->redirectToRoute('incc_search_results', ['keyword' => $keyword]);
        }

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $sort = $request->request->get('sort', '');
        if ($request->isMethod('post')) {
            $request->getSession()->set('search_sort', $sort);
        } elseif ($request->getSession()->has('search_sort')) {
            $sort = $request->getSession()->get('search_sort', '');
        }

        $results = $searchRepository->search(
            $request->attributes->get('keyword'),
            $request->attributes->get('offset'),
            $request->attributes->get('limit'),
            $sort,
        );

        $this->data['form'] = $form->createView();
        $this->data['query']['sort'] = $sort;
        $this->data['query']['offset'] = $results->getOffset();
        $this->data['query']['limit'] = $results->getLimit();
        $this->data['page']['title'] =  sprintf('\'%s\' results', $request->attributes->get('keyword'));

        $this->data['results'] = $results;

        foreach ($this->data['results']->getResults() as $key => $result) {
            $this->data['results']->updateResultPropertyByKey(
                $key,
                'relevance',
                number_format($result['relevance'], 2)
            );
            $author = $userRepository->findOneBy([
                'id' => $result['author'],
            ]);
            $this->data['results']->updateResultPropertyByKey(
                $key,
                'author',
                !empty($author) ? $author->getDisplayName() : 'Unknown',
            );
            switch ($result['type']) {
                case 'Image':
                    $this->data['results']->updateResultPropertyByKey(
                        $key,
                        'url',
                        $this->generateUrl('incc_resource_edit', [
                            'type' => 'images',
                            'filename' => $result['sub_title']]
                        )
                    );
                    break;

                case 'Series':
                    $this->data['results']->updateResultPropertyByKey(
                        $key,
                        'url',
                        $this->generateUrl('incc_series_edit', ['id' => $result['id']])
                    );
                    break;

                case 'Page':
                case 'Post':
                    $link = $urlRepository->findOneBy([
                        'content' => $result['id'],
                        'default' => true,
                    ]);
                    $this->data['results']->updateResultPropertyByKey(
                        $key,
                        'url',
                        sprintf(
                            '/incc/%s/%s',
                            strtolower($result['type']),
                            !empty($link) ? $link->getLink() : ''
                        ),
                    );
            }
        }
        $this->data['total'] = $results->getTotal();
        $this->data['keyword'] = $request->attributes->get('keyword');

        return $this->render('inadmin/page/search/results.html.twig', $this->data);
    }
}
