<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Attribute\Route;

class CategoryManagerController extends AbstractInachisController
{
    #[Route("/incc/ax/categoryManager/get", methods: [ "POST" ])]
    public function content(): Response
    {
//        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->data['categories'] =  $this->entityManager->getRepository(Category::class)->getRootCategories();
//        return $this->render('inadmin/dialog/category-manager-list.html.twig', $this->data);
    }

    #[Route("/incc/ax/categoryList/get", methods: [ "POST" ])]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $categoryManager = new CategoryManager(Application::getInstance()->getService('em'));
//        self::$data['categories'] = empty($request->paramsPost()->get('q')) ?
//            $categoryManager->getRootCategories() :
//            $categoryManager->getAll(
//                0,
//                25,
//                array(
//                    'q.title LIKE :title',
//                    array('title' => '%' . $request->paramsPost()->get('q') . '%')
//                ),
//                array('q.title')
//            );
//        self::$data['totalResults'] = $categoryManager->getAllCount(array(
//            'q.title LIKE :title',
//            array('title' => '%' . $request->paramsPost()->get('q') . '%')
//        ));
//        $result = array(
//            'items' => array(),
//            'totalCount' => self::$data['totalResults']
//        );
//        if (!empty(self::$data['categories'])) {
//            foreach (self::$data['categories'] as $category) {
//                $title = $category->getTitle();
//                if (isset($result['items'][$title])) {
//                    $result['items'][$result['items'][$title]->path] = $result['items'][$title];
//                    $result['items'][$result['items'][$title]->path]->text = $result['items'][$title]->path;
//                    unset($result['items'][$title]);
//                    $title = $category->getFullPath();
//                }
//                $result['items'][$title] = (object) array(
//                    'id' => $category->getId(),
//                    'text' => $title,
//                    'path' => $category->getFullPath()
//                );
//            }
//            $result['items'] = array_values($result['items']);
//        }
        $result = ''; // remove once above is sorted
        return $this->json((object) $result);
    }

    #[Route("/incc/ax/categoryManager/save", methods: [ "POST" ])]
    public function save(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $categoryManager = new CategoryManager(Application::getInstance()->getService('em'));
//        $newCategory = $categoryManager->create(array(
//            'title' => $request->paramsPost()->get('categoryName'),
//            'parent' => $categoryManager->getById($request->paramsPost()->get('parentId'))
//        ));
//        $categoryManager->save($newCategory);
    }
}
