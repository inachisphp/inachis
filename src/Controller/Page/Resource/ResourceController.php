<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Resource;

use App\Controller\AbstractInachisController;
use App\Entity\Download;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Form\ResourceType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

//use Symfony\Component\HttpFoundation\File\UploadedFile;

class ResourceController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/resources/{type}/{offset}/{limit}",
        name: "incc_resource_list",
        requirements: [
            "type" => "(images|downloads)",
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 25 ],
        methods: [ "GET", "POST" ],
    )]
    public function list(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $typeClass = match ($request?->get('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\'), 1);
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('incc_resource_list', [
                'type' => $request->attributes->get('type'),
            ]))
            ->getForm()
        ;
        $form->handleRequest($request);
        $filters = array_filter($request->request->all('filter', []));
        $offset = (int) $request->attributes->get('offset', 0);
        $limit = (int) $request->attributes->get(
            'limit',
            $this->entityManager->getRepository($typeClass)->getMaxItemsToShow()
        );
        $sort = $request->request->get('sort', 'title asc');
        if ($request->isMethod('post')) {
            $request->getSession()->set($type . '_sort', $sort);
        } elseif ($request->getSession()->has($type . '_sort')) {
            $sort = $request->getSession()->get($type . '_sort', '');
        }
        $this->data['dataset'] = $this->entityManager->getRepository($typeClass)->getFiltered(
            $filters,
            $offset,
            $limit,
            $sort,
        );
        $this->data['form'] = $form->createView();
        $this->data['filters'] = $filters;
        $this->data['page']['type'] = $request->attributes->get('type');
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['sort'] = $sort;
        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = $type . 's';
        $this->data['limitKByte'] = Image::WARNING_FILESIZE;
        $this->data['limitSize'] = Image::WARNING_DIMENSIONS;

        return $this->render('inadmin/page/resource/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param Filesystem $filesystem
     * @param string $imageDirectory
     * @return Response
     */
    #[Route('/incc/resources/{type}/{filename}',
        name: "incc_resource_edit",
        requirements: [
            "type" => "(images|downloads)",
        ],
        methods: [ "GET", "POST" ],
    )]
    public function edit(
        Request $request,
        Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//            "filename" => "[a-zA-Z0-9\-\_]\.(jpe?g|heic|png)",
        $typeClass = match ($request->attributes->get('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\'), 1);
        $resource = $this->entityManager->getRepository($typeClass)->findOneBy([
            'id' => $request->attributes->get('filename'),
        ]);
        if (empty($resource)) {
            return $this->redirectToRoute(
                'incc_resource_list',
                [
                    'type' => $request->attributes->get('type'),

                ],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
        }
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);
        if ($type === 'Image') {
            $this->data['usages']['posts'] = $this->entityManager->getRepository(Page::class)->getPostsUsingImage($resource);
            $this->data['usages']['series'] = $this->entityManager->getRepository(Series::class)->getSeriesUsingImage($resource);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $resource = $form->getData();
            if (isset($request->request->all('resource')['delete'])) {
                $filename = $imageDirectory . $resource->getFilename();
                if ($type === 'Image' &&
                    sizeof($this->data['usages']['posts']) === 0 &&
                    sizeof($this->data['usages']['series']) === 0 &&
                    $filesystem->exists($filename)) {
                    try {
                        $filesystem->remove($filename);
                        $this->entityManager->getRepository($typeClass)->remove($resource);
                        $this->addFlash('success', 'Resource deleted.');
                        return $this->redirectToRoute(
                            'incc_resource_list',
                            [
                                'type' => $request->attributes->get('type'),

                            ],
                            Response::HTTP_PERMANENTLY_REDIRECT
                        );
                    } catch (IOExceptionInterface $e) {
                        $this->addFlash('error', 'Failed to remove file.');
                    }
                }
            }
            $resource->setAuthor($this->getUser());
            $resource->setModDate(new \DateTime('now'));
            $this->entityManager->persist($resource);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            return $this->redirectToRoute(
                'incc_resource_edit', [
                    'type' => $request->attributes->get('type'),
                    'filename' => $resource->getId(),
                ]
            );
        }
        $this->data['form'] = $form->createView();
        $this->data['page']['type'] = $request->attributes->get('type');
        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = sprintf('%s: %s', $type, $resource->getTitle());
        $this->data['resource'] = $resource;
        if ($type === 'Image') {
            $this->data['usages']['posts'] = $this->entityManager->getRepository(Page::class)->getPostsUsingImage($resource);
            $this->data['usages']['series'] = $this->entityManager->getRepository(Series::class)->getSeriesUsingImage($resource);
            $fullImagePath = $resource->getFilename();
            if (!str_starts_with($fullImagePath, 'http')) {
                $fullImagePath = $imageDirectory . $fullImagePath;
            }
            $sizes = getimagesize($fullImagePath);
            $this->data['channels'] = $sizes['channels'] ?? '';
            $this->data['bits'] = $sizes['bits'] ?? '';
            $this->data['limitKByte'] = Image::WARNING_FILESIZE;
            $this->data['limitSize'] = Image::WARNING_DIMENSIONS;
        }

        return $this->render('inadmin/page/resource/edit.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param string $imageDirectory
     * @return JsonResponse
     */
    #[Route("/incc/resource/image/upload", name: "incc_resource_upload_image", methods: [ "POST", "PUT" ])]
    public function uploadImage(
        Request $request,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (empty($request->files->get("image"))) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        } elseif (empty($request->request->all('image')['title'])) {
            return new JsonResponse(['error' => 'No title provided'], 400);
        }
        $uploadedFile = $request->files->get("image")['imageFile'];
        // @todo handle optimise image which reduces to Image::WARNING_SIZE max and 85% compression if JPEG
        // @todo if HEIC, and HEIC supported, convert to JPEG
        $dimensions = getimagesize($uploadedFile->getRealPath());
        $ctx = hash_init('sha256');
        $fp = fopen($uploadedFile->getRealPath(), 'rb');
        while (!feof($fp)) {
            hash_update($ctx, fread($fp, 8192));
        }
        fclose($fp);
        // @todo change filename to use the title for better SEO
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        $image = new Image();
        $image
            ->setTitle($request->request->all('image')['title'])
            ->setDescription($request->request->all('image')['description'])
            ->setAltText($request->request->all('image')['altText'])
            ->setFilesize($uploadedFile->getSize())
            ->setFiletype($uploadedFile->getMimeType())
            ->setFilename($newFilename)
            ->setChecksum(hash_final($ctx))
            ->setDimensionX($dimensions[0])
            ->setDimensionY($dimensions[1])
        ;

        try {
            $uploadedFile->move($imageDirectory, $newFilename);
        } catch (FileException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
        $this->entityManager->persist($image);
        $this->entityManager->flush();
        return new JsonResponse(['OK' => $image->getId()], 200);
    }
}
