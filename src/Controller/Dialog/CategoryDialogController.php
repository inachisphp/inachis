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
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
class CategoryDialogController extends AbstractInachisController
{
    /**
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
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("incc/ax/categoryList/get", methods: [ "POST" ])]
    public function getCategoryManagerListContent(Request $request, CategoryRepository $categoryRepository): Response
    {
        $categories = empty($request->request->get('q')) ?
            $categoryRepository->findBy(['parent' => null]) :
            $categoryRepository->findByTitleLike($request->request->get('q'));
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
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route("incc/ax/categoryManager/save", methods: [ "POST" ])]
    public function saveCategoryManagerContent(
        Request $request,
        CategoryRepository $categoryRepository
    ): Response {
        $category = $request->request->get('id') !== '-1' ?
            $categoryRepository->findOneBy(['id' => $request->request->get('id')]) :
            new Category();
        $categoryRepository->hydrate($category, $request->request->all());
        $category->setParent(
            $request->request->get('parentID') !== '-1' ?
                $categoryRepository->findOneBy(['id' => $request->request->get('parentID')]) :
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

    /**
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
        $category = $categoryRepository->findOneBy(['id' => $request->request->get('id')]);
        $count = $pageRepository->getPagesWithCategoryCount($category);
        foreach ($category->getChildren() as $child) {
            $count += $pageRepository->getPagesWithCategoryCount($child);
        }
        return new JsonResponse([ 'count' => $count]);
    }

    /**
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
        $this->entityManager->getRepository(Category::class)->remove($category);
        return new JsonResponse();
    }
}
