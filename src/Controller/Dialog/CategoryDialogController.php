<?php

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Controller\ZipStream;
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\ImageType;
use App\Parser\ArrayToMarkdown;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

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

        return $this->render('inadmin/dialog/categoryManager.html.twig', $this->data);
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
        $category = $this->entityManager->getRepository(Category::class)->create($request->request->all());
        if ($request->request->get('parentID') > 0) {
            $category->setParent(
                $this->entityManager->getRepository(Category::class)->findOneById($request->request->get('parentID'))
            );
        }
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return new JsonResponse($category, Response::HTTP_OK);
    }
}
