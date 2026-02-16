<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Resource;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\{Download, Image};
use Inachis\Form\ResourceType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\{DownloadRepository, ImageRepository, PageRepository, SeriesRepository};
use Inachis\Service\Resource\ImageFileService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
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
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        DownloadRepository $downloadRepository,
        ImageRepository $imageRepository,
    ): Response {
        $typeClass = match($request->attributes->get('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\'), 1);
        $repository = match($type) {
            'Download' => $downloadRepository,
            default => $imageRepository,
        };
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('incc_resource_list', [
                'type' => strtolower($type) . 's',
            ]))
            ->getForm();
        $form->handleRequest($request);
        $contentQuery = $contentQueryParameters->process(
            $request,
            $repository,
            strtolower($type),
            'title asc',
        );
        $this->data['dataset'] = $repository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['form'] = $form->createView();
        $this->data['query'] = $contentQuery;
        $this->data['page']['type'] = strtolower($type) . 's';
        $this->data['page']['tab'] = strtolower($type);
        $this->data['page']['title'] = $type . 's';
        $this->data['limitKByte'] = Image::WARNING_FILESIZE;
        $this->data['limitSize'] = Image::WARNING_DIMENSIONS;
        $this->data['allowedTypes'] = Image::ALLOWED_MIME_TYPES;

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
        DownloadRepository $downloadRepository,
        ImageRepository $imageRepository,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory
    ): Response {
//            "filename" => "[a-zA-Z0-9\-\_]\.(jpe?g|heic|png)",
        $typeClass = match ($request->attributes->get('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\'), 1);
        $repository = match($type) {
            'Download' => $downloadRepository,
            default => $imageRepository,
        };
        $resource = $repository->findOneBy([
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
            $this->data['usages']['posts'] = $pageRepository->getPostsUsingImage($resource);
            $this->data['usages']['series'] = $seriesRepository->getSeriesUsingImage($resource);
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
                        $repository->remove($resource);
                        $this->addFlash('success', 'Resource deleted.');
                        return $this->redirectToRoute(
                            'incc_resource_list',
                            [
                                'type' => $request->attributes->get('type'),

                            ],
                            Response::HTTP_PERMANENTLY_REDIRECT
                        );
                    } catch (IOException $e) {
                        $this->addFlash('error', 'Failed to remove file.');
                        return $this->redirectToRoute(
                            'incc_resource_edit', [
                                'type' => $request->attributes->get('type'),
                                'filename' => $resource->getId(),
                            ]
                        );
                    }
                }
            }
            $resource->setAuthor($this->getUser());
            $resource->setModDate(new DateTimeImmutable());
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
            try {
                $sizes = $resource->getImageProperties($imageDirectory);
            } catch (FileNotFoundException $exception) {
                $this->addFlash('error', 'Associated image file could not be found');
            }
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
        ImageFileService $imageFileService,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory): JsonResponse
    {
        $imageData = $request->request->all('image');
        /** @var UploadedFile $uploadedFileInput */
        $uploadedFileInput = $request->files->get('image')['imageFile'] ?? null;

        if (!$uploadedFileInput) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        } elseif (empty($imageData['title'])) {
            return new JsonResponse(['error' => 'No title provided'], 400);
        }

        try {
            // Step 1: Convert HEIC to JPEG if required
            $uploadedFile = $imageFileService->convertHEICToJPEG($uploadedFileInput);

            // Step 2: Optimise if required (to WebP or AVIF if available)
            if (!empty($imageData['optimise'])) {
                $uploadedFile = $imageFileService->optimise($uploadedFile);
            }

            // Step 3: Extract dimensions
            $dimensions = $imageFileService->getImageDimensions($uploadedFile);
            if ($dimensions === false) {
                throw new \RuntimeException('Unable to read image dimensions.');
            }

            // Step 4: Generate checksum
            $checksum = $imageFileService->createChecksum($uploadedFile);

            // Step 5: Create safe filename
            // @todo change filename to use the title for better SEO?
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            // Step 6: Move file to storage directory
            $uploadedFile->move($imageDirectory, $newFilename);

            // Step 7: Create db record
            $image = new Image();
            $image
                ->setTitle($imageData['title'])
                ->setDescription($imageData['description'] ?? null)
                ->setAltText($imageData['altText'] ?? null)
                ->setFilesize($uploadedFile->getSize())
                ->setFiletype($uploadedFile->getMimeType())
                ->setFilename($newFilename)
                ->setChecksum($checksum)
                ->setDimensionX($dimensions[0])
                ->setDimensionY($dimensions[1]);

            $this->entityManager->persist($image);
            $this->entityManager->flush(); 

            return new JsonResponse([
                'success' => true,
                'filename' => $newFilename,
                'checksum' => $checksum,
                'dimensions' => [
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                ],
            ]);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'File upload failed: ' . $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
