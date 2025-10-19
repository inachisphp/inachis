<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Controller\ZipStream;
use App\Entity\Category;
use App\Entity\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryDialogController extends AbstractInachisController
{
    /**
     * @return Response
     */
    #[Route("/incc/ax/categoryManager/get", methods: [ "POST" ])]
    public function getCategoryManagerContent(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->data['categories'] = $this->entityManager->getRepository(Category::class)->findByParent(null);

        return $this->render('inadmin/dialog/category-manager.html.twig', $this->data);
    }


    /**
     * @return Response
     */
    #[Route("/incc/ax/categoryManager/list", methods: [ "POST" ])]
    public function getCategoryManagerList(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->data['categories'] = $this->entityManager->getRepository(Category::class)->findByParent(null);

        return $this->render('inadmin/dialog/category-manager-list.html.twig', $this->data);
    }

    /**
     * @param Request         $request
     * @param LoggerInterface $logger
     * @return Response
     */
    #[Route("incc/ax/categoryList/get", methods: [ "POST" ])]
    public function getCategoryManagerListContent(Request $request, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $categories = empty($request->get('q')) ?
            $this->entityManager->getRepository(Category::class)->findByParent(null) :
            $this->entityManager->getRepository(Category::class)->findByTitleLike($request->request->get('q'));
        $result = [];
        // Below code is used to handle where categories exist with the same name under multiple locations
        if (!empty($categories)) {
            $result['items'] = [];
            foreach ($categories as $category) {
                $title = $category->getTitle();
                if (isset($result['items'][$title])) {
                    $result['items'][$result['items'][$title]->path] = $result['items'][$title];
                    $result['items'][$result['items'][$title]->path]->text = $result['items'][$title]->path;
                    unset($result['items'][$title]);
                    $title = $category->getFullPath();
                }
                $result['items'][$title] = (object) [
                    'id'   => $category->getId(),
                    'text' => $title,
                    'path' => $category->getFullPath(),
                ];
            }
            $result = array_values($result['items']);
        }

        return new JsonResponse(
            [
                'items'      => $result,
                'totalCount' => count($result),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("incc/ax/categoryManager/save", methods: [ "POST" ])]
    public function saveCategoryManagerContent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $category = $request->get('id') !== '-1' ?
            $this->entityManager->getRepository(Category::class)->findOneById($request->get('id')) :
            new Category();
        $this->entityManager->getRepository(Category::class)->hydrate($category, $request->request->all());
        $category->setParent(
            $request->request->get('parentID') !== '-1' ?
                $this->entityManager->getRepository(Category::class)->findOneById($request->request->get('parentID')) :
                null
        );
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return new JsonResponse(
            [
                'success' => '<span class="material-icons">check_circle</span> Category saved',
            ],
            Response::HTTP_OK
        );
    }

    #[Route("incc/ax/categoryManager/usage", methods: [ "POST" ])]
    public function getCategoryUsages(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $category = $this->entityManager->getRepository(Category::class)->findOneById($request->get('id'));
        $count = $this->entityManager->getRepository(Page::class)->getPagesWithCategoryCount($category);
        foreach ($category->getChildren() as $child) {
            $count += $this->entityManager->getRepository(Page::class)->getPagesWithCategoryCount($child);
        }
        return new JsonResponse([ 'count' => $count]);
    }

    #[Route("incc/ax/categoryManager/delete", methods: [ "POST" ])]
    public function deleteCategory(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $category = $this->entityManager->getRepository(Category::class)->findOneById($request->get('id'));
        $count = $this->entityManager->getRepository(Page::class)->getPagesWithCategoryCount($category);

        if ($count > 0) {
            return new JsonResponse(
                [
                    'error' => sprintf('<span class="material-icons">warning</span> %d categories present', $count)
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $this->entityManager->getRepository(Category::class)->remove($category);
        return new JsonResponse();
    }
}
