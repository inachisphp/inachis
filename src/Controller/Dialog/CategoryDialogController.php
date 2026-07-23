<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Category;
use Inachis\Repository\Content\{CategoryRepository, PageRepository};
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Category Dialog Controller
 */
class CategoryDialogController extends AbstractInachisController
{

    /**
     * Get the category manager content
     *
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("/incp/ax/categoryManager/get", methods: [ "POST" ])]
    public function getCategoryManagerContent(CategoryRepository $categoryRepository): Response
    {
        return $this->render('inadmin/dialog/category-manager.html.twig', [
            'categories' => $categoryRepository->findBy(['parent' => null]),
        ]);
    }

    /**
     * Get the category manager list
     *
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("/incp/ax/categoryManager/list", methods: [ "POST" ])]
    public function getCategoryManagerList(CategoryRepository $categoryRepository): Response
    {
        return $this->render('inadmin/dialog/category-manager-list.html.twig', [
            'categories' => $categoryRepository->findBy(['parent' => null]),
        ]);
    }

    /**
     * Get the category manager list content
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("incp/ax/categoryList/get", methods: [ "POST" ])]
    public function getCategoryManagerListContent(Request $request, CategoryRepository $categoryRepository): Response
    {
        /** @var array<int, Category> $categories */
        $categories = empty($request->request->getString('q')) ?
            $categoryRepository->findBy(['parent' => null]) :
            $categoryRepository->findByTitleLike($request->request->getString('q'));
        /** @var array<int, Category> $result */
        $result = [];
        // Below code is used to handle where categories exist with the same name under multiple locations but are distinct
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
     * Save the category manager content
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("incp/ax/categoryManager/save", methods: [ "POST" ])]
    public function saveCategoryManagerContent(
        Request $request,
        CategoryRepository $categoryRepository
    ): Response {
        /** @var Category $category */
        $category = $request->request->getString('id') !== '-1' ?
            $categoryRepository->findOneBy(['id' => $request->request->getString('id')]) :
            new Category();
        /** @var Category|null $parentCategory */
        $parentCategory = $request->request->getString('parentID') !== '-1' ?
            $categoryRepository->findOneBy(['id' => $request->request->getString('parentID')]) :
            null;
        $categoryRepository->hydrate($category, $request->request->all());
        $category->setParent($parentCategory);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return new JsonResponse(
            [
                'success' => '<span class="material-icons">check_circle</span> Category saved',
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Get the category usages
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @param PageRepository $pageRepository
     * @return JsonResponse
     */
    #[Route("incp/ax/categoryManager/usage", methods: [ "POST" ])]
    public function getCategoryUsages(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository
    ): JsonResponse {
        $id = $request->request->getString('id');
        /** @var Category|null $category */
        $category = $categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['count' => 0]);
        }

        $count = $pageRepository->getPagesWithCategoryCount($category);
        foreach ($category->getChildren() as $child) {
            $count += $pageRepository->getPagesWithCategoryCount($child);
        }
        return new JsonResponse([ 'count' => $count]);
    }

    /**
     * Delete the category
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("incp/ax/categoryManager/delete", methods: [ "POST" ])]
    public function deleteCategory(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository
    ): Response
    {
        /** @var Category $category */
        $category = $categoryRepository->findOneBy(['id' => $request->request->getString('id')]);
        $count = $pageRepository->getPagesWithCategoryCount($category);

        if ($count > 0) {
            return new JsonResponse(
                [
                    'error' => sprintf(
                        '<span class="material-icons">warning</span> %d categories present',
                        $count,
                    )
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $categoryRepository->remove($category);
        return new JsonResponse();
    }
}
