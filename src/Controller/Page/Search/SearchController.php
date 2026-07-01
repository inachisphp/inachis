<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Search;

use Exception;
use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\SearchRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Repository\User\UserRepository;
use Ramsey\Uuid\Uuid;
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
    #[Route("/incc/search/results/{keyword}/{limit}/{offset}",
        name: "incc_search_results",
        requirements: [
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "keyword" => null, "limit" => 25, "offset" => 0, ],
        methods: [ "GET", "POST" ],
    )]
    public function results(
        Request $request,
        SearchRepository $searchRepository,
        UrlRepository $urlRepository,
        UserRepository $userRepository,
    ): Response {
        if ($request->attributes->getString('keyword') === ' ' && !empty($request->request->getString('keyword', ''))) {
            $keyword = str_replace('/', '', $request->request->getString('keyword', ''));
            $keyword = preg_replace('/(?:%25)*2[fF]/', '', $keyword);
            return $this->redirectToRoute('incc_search_results', ['keyword' => $keyword]);
        }

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $sort = $request->request->getString('sort', '');
        if ($request->isMethod('post')) {
            $request->getSession()->set('search_sort', $sort);
        } elseif ($request->getSession()->has('search_sort')) {
            /** @var string */
            $sort = $request->getSession()->get('search_sort', '');
        }

        $results = $searchRepository->search(
            $request->attributes->getString('keyword'),
            $request->attributes->getInt('limit'),
            $request->attributes->getInt('offset'),
            $sort,
        );

        $this->viewModel->page->title = sprintf('\'%s\' results', $request->attributes->getString('keyword'));


        foreach ($results->getResults() as $key => $result) {
            $uuidString = Uuid::fromBytes($result['id'])->toString();
            // $results->updateResultPropertyByKey($key, 'id', $uuidString);
            
            $results->updateResultPropertyByKey(
                $key,
                'relevance',
                number_format($result['relevance'], 2)
            );
            $author = $userRepository->findOneBy([
                'id' => $uuidString,
            ]);
            $results->updateResultPropertyByKey(
                $key,
                'author',
                !empty($author) ? $author->getDisplayName() : 'Unknown',
            );
            switch ($result['type']) {
                case 'Image':
                    $results->updateResultPropertyByKey(
                        $key,
                        'url',
                        $this->generateUrl('incc_resource_edit', [
                            'type' => 'images',
                            'filename' => $uuidString]
                        )
                    );
                    break;

                case 'Series':
                    $results->updateResultPropertyByKey(
                        $key,
                        'url',
                        $this->generateUrl('incc_series_edit', ['id' => $uuidString])
                    );
                    break;

                case 'Page':
                case 'Post':
                    $link = $urlRepository->findOneBy([
                        'content' => $uuidString,
                        'default' => true,
                    ]);
                    $results->updateResultPropertyByKey(
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
        
        return $this->render('inadmin/page/search/results.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'keyword' => $request->attributes->getString('keyword'),
            'query' => [
                'sort' => $sort,
                'offset' => $results->getOffset(),
                'limit' => $results->getLimit(),
            ],
            'results' => $results,
            'total' => $results->getTotal(),
        ]);
    }
}
