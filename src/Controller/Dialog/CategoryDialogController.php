<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Controller\ZipStream;
use Inachis\Entity\Category;
use Inachis\Entity\Page;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\PageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Category Dialog Controller
 */
#[IsGranted('ROLE_ADMIN')]
class CategoryDialogController extends AbstractInachisController
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Get the category manager content
     *
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("/incc/ax/categoryManager/get", methods: [ "POST" ])]
    public function getCategoryManagerContent(CategoryRepository $categoryRepository): Response
    {
        $this->data['categories'] = $categoryRepository->findBy(['parent' => null]);

        return $this->render('inadmin/dialog/category-manager.html.twig', $this->data);
    }

    /**
     * Get the category manager list
     *
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("/incc/ax/categoryManager/list", methods: [ "POST" ])]
    public function getCategoryManagerList(CategoryRepository $categoryRepository): Response
    {
        $this->data['categories'] = $categoryRepository->findBy(['parent' => null]);

        return $this->render('inadmin/dialog/category-manager-list.html.twig', $this->data);
    }

    /**
     * Get the category manager list content
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("incc/ax/categoryList/get", methods: [ "POST" ])]
    public function getCategoryManagerListContent(Request $request, CategoryRepository $categoryRepository): Response
    {
        /** @var array<int, Category> $categories */
        $categories = empty($request->request->get('q')) ?
            $categoryRepository->findBy(['parent' => null]) :
            $categoryRepository->findByTitleLike($request->request->get('q'));
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
    #[Route("incc/ax/categoryManager/save", methods: [ "POST" ])]
    public function saveCategoryManagerContent(
        Request $request,
        CategoryRepository $categoryRepository
    ): Response {
        /** @var Category $category */
        $category = $request->request->get('id') !== '-1' ?
            $categoryRepository->findOneBy(['id' => $request->request->get('id')]) :
            new Category();
        /** @var Category|null $parentCategory */
        $parentCategory = $request->request->get('parentID') !== '-1' ?
            $categoryRepository->findOneBy(['id' => $request->request->get('parentID')]) :
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
    #[Route("incc/ax/categoryManager/usage", methods: [ "POST" ])]
    public function getCategoryUsages(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository
    ): JsonResponse {
        $id = $request->request->get('id');
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
    #[Route("incc/ax/categoryManager/delete", methods: [ "POST" ])]
    public function deleteCategory(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository
    ): Response
    {
        /** @var Category $category */
        $category = $categoryRepository->findOneBy(['id' => $request->request->get('id')]);
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
