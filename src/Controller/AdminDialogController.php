<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\ImageType;
use App\Parser\ArrayToMarkdown;
use App\Repository\PageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class AdminDialogController extends AbstractInachisController
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
     * @return Response
     */
    #[Route("/incc/ax/imageManager/get", methods: [ "POST" ])]
    public function getImageManagerList(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->data['form'] = $this->createForm(ImageType::class)->createView();
        $this->data['allowedTypes'] = Image::ALLOWED_TYPES;
        // @todo add pagination
        $this->data['images'] = $this->entityManager->getRepository(Image::class)->getAll(0, 250);
        $this->data['image_count'] = sizeof($this->data['images']);
        return $this->render('inadmin/dialog/imageManager.html.twig', $this->data);
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
        $category->setParent(
            $this->entityManager->getRepository(Category::class)->findOneById($request->request->get('parentID'))
        );
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return new JsonResponse($category, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/export/get", methods: [ "POST" ])]
    public function export(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('inadmin/dialog/export.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     * @throws ExceptionInterface
     */
    #[Route("/incc/ax/export/output", methods: [ "POST" ])]
    public function performExport(Request $request, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $posts = [];
        if (empty($request->request->get('postId'))) {
            return new Response(null, Response::HTTP_EXPECTATION_FAILED);
        }
        $posts = $this->entityManager->getRepository(Page::class)->getFilteredIds(
            $request->request->get('postId')
        )->getIterator()->getArrayCopy();
        if (empty($posts)) {
            return new Response(null, Response::HTTP_EXPECTATION_FAILED);
        }

        $normalisedAttributes = [
            'title',
            'subTitle',
            'postDate',
            'content',
            'featureSnippet',
            'featureImage',
        ];
        if (!empty($request->request->get('export_categories'))) {
            $normalisedAttributes[] = 'categories';
        }
        if (!empty($request->request->get('export_tags'))) {
            $normalisedAttributes[] = 'tags';
        }
        $posts = $serializer->normalize(
            $posts,
            null,
            [
                AbstractNormalizer::ATTRIBUTES => $normalisedAttributes,
            ]
        );

        $format = $request->request->get('export_format');
        $response = new Response();
        switch ($format) {
            case 'json':
                $posts = json_encode($posts);
                $response->headers->set('Content-Type', 'application/json');
                break;

            case 'xml':
                $encoder = new XmlEncoder();
                $posts = $encoder->encode($posts, '');
                $response->headers->set('Content-Type', 'text/xml');
                break;

            case 'md':
            default:
                $format = 'md';
                $markdownPosts = [];
                // @todo use https://packagist.org/packages/maennchen/zipstream-php to zip contents
                // export_zip
                foreach ($posts as $post) {
                    $markdownPosts[] = ArrayToMarkdown::parse($post);
                }
        }
        $response->setContent($posts);

        $filename = date('YmdHis');
        if (!empty($request->request->get('export_name'))) {
            $filename = $request->request->get('export_name');
        }
        $filename .= '.' . $format;

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            )
        );

        return $response;
    }

    /**
     * @param Request         $request
     * @param LoggerInterface $logger
     * @return Response
     */
    #[Route("incc/ax/tagList/get", methods: [ "POST" ])]
    public function getTagManagerListContent(Request $request, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $tags = $this->entityManager->getRepository(Tag::class)->findByTitleLike($request->request->get('q'));
        $result = [];
        // Below code is used to handle where tags exist with the same name under multiple locations
        if (!empty($tags)) {
            $result['items'] = [];
            foreach ($tags as $tag) {
                $title = $tag->getTitle();
                $result['items'][$title] = (object) [
                    'id'   => $tag->getId(),
                    'text' => $title,
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
}
