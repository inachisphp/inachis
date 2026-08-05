<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Resource;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Media\Download;
use Inachis\Entity\Media\Image;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\ImageType;
use Inachis\Form\ResourceType;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\DownloadRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\File\DownloadFileService;
use Inachis\Service\Resource\ImageFileService;
use Inachis\Service\Waste\WasteManagerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ResourceController extends AbstractInachisController
{
    /**
     * @throws \Exception
     */
    #[Route('/incp/resources/{type}/{limit}/{offset}',
        name: 'incp_resource_list',
        requirements: [
            'type' => '(images|downloads)',
            'limit' => "\d+",
            'offset' => "\d+",
        ],
        defaults: ['limit' => 25, 'offset' => 0],
        methods: ['GET', 'POST'],
    )]
    #[RequiresPermission(
        resource: [
            PermissionResource::IMAGE,
            PermissionResource::DOWNLOAD,
        ],
        action: PermissionAction::VIEW,
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        DownloadRepository $downloadRepository,
        ImageRepository $imageRepository,
        ViewStateManager $viewStateManager,
    ): Response {
        $typeClass = match ($request->attributes->getString('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\') ?: '', 1);
        $repository = match ($type) {
            'Download' => $downloadRepository,
            default => $imageRepository,
        };
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('incp_resource_list', [
                'type' => strtolower($type).'s',
            ]))
            ->getForm();
        $form->handleRequest($request);

        $params = $viewStateManager->load(
            $request,
            strtolower($type),
            new ViewStateDefaults(
                sort: 'title asc',
                view: $typeClass === Download::class ? 'table' : 'grid',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                strtolower($type),
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incp_resource_list', [
                'type' => $request->attributes->getString('type'),
                'limit' => $request->attributes->getInt('limit'),
                'offset' => $request->attributes->getInt('offset'),
            ]);
        }

        if ($repository instanceof ImageRepository && 'null' === $request->query->getString('altText', '')) {
            $dataset = $repository->getImagesWithoutAltText(
                $params->getLimit(),
                $params->getOffset(),
            );
        } else {
            $dataset = $repository->getFiltered(
                $params->getFilters(),
                $params->getLimit(),
                $params->getOffset(),
                $params->getSort(),
            );
        }

        $this->viewModel->page->title = $type.'s';
        $this->viewModel->page->type = strtolower($type).'s';
        $this->viewModel->page->tab = strtolower($type);

        return $this->render('inadmin/page/resource/list.html.twig', [
            'viewModel' => $this->viewModel,
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'dataset' => $dataset,
            'form' => $form->createView(),
            'limitKByte' => Image::WARNING_FILESIZE,
            'limitSize' => Image::WARNING_DIMENSIONS,
            'query' => $params,
            'showUploadDialog' => $request->query->has('upload') && 'true' === $request->query->getString('upload'),
        ]);
    }

    #[Route('/incp/resources/{type}/{filename}',
        name: 'incp_resource_edit',
        requirements: [
            'type' => '(images|downloads)',
        ],
        methods: ['GET', 'POST'],
    )]
    public function edit(
        Request $request,
        Filesystem $filesystem,
        DownloadFileService $downloadFileService,
        DownloadRepository $downloadRepository,
        ImageRepository $imageRepository,
        PageRepository $pageRepository,
        SeriesRepository $seriesRepository,
        WasteManagerService $wasteManagerService,
        #[Autowire('%kernel.project_dir%')] string $projectDirectory,
    ): Response {
        //            "filename" => "[a-zA-Z0-9\-\_]\.(jpe?g|heic|png)",
        $typeClass = match ($request->attributes->getString('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\') ?: '', 1);
        $repository = match ($type) {
            'Download' => $downloadRepository,
            default => $imageRepository,
        };
        
        $filenameParam = $request->attributes->getString('filename');
        if ('new' === $filenameParam) {
            $resource = new $typeClass();
        } else {
            $resource = $repository->findOneBy([
                'id' => $request->attributes->getString('filename'),
            ]);
            if (empty($resource)) {
                return $this->redirectToRoute(
                    'incp_resource_list',
                    [
                        'type' => $request->attributes->getString('type'),
                    ],
                    Response::HTTP_PERMANENTLY_REDIRECT,
                );
            }
        }

        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        $usages = [];
        if ($resource instanceof Image) {
            $usages = [
                'posts' => $pageRepository->getPostsUsingImage($resource),
                'series' => $seriesRepository->getSeriesUsingImage($resource),
            ];
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $resource = $form->getData();

            if (isset($request->request->all('resource')['delete'])) {
                $storageDir = $resource instanceof Download ? 
                    $projectDirectory . '/var/uploads/' : 
                    $projectDirectory . '/public/imgs/';
                $filePath = $storageDir . $resource->getFilename();

                if (($resource instanceof Image
                    && isset($usages['posts'])
                    && empty($usages['posts'])
                    && 0 === $usages['series']->count()) ||
                    $resource instanceof Download
                ) {
                    try {
                        if (!$filesystem->exists($filePath)) {
                            $this->addFlash('error', 'The file for this resource does not exist and so will not be recoverable.');
                        } else {
                            $wasteManagerService->sendToWaste($resource);
                        }
                        $repository->remove($resource);
                        $this->addFlash('success', 'Resource deleted.');

                        return $this->redirectToRoute(
                            'incp_resource_list',
                            [
                                'type' => $request->attributes->getString('type'),
                            ],
                            Response::HTTP_PERMANENTLY_REDIRECT,
                        );
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to remove file.');

                        return $this->redirectToRoute(
                            'incp_resource_edit', [
                                'type' => $request->attributes->getString('type'),
                                'filename' => $resource->getId(),
                            ],
                        );
                    }
                } else {
                    $this->addFlash('error', 'Can\'t remove file as it is in use');
                }
            }

            // Handle File Processing for NEW Download items on Submit
            if ($resource instanceof Download && null === $resource->getId()) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $uploadedFile */
                $uploadedFile = $form->get('file')->getData();

                if ($uploadedFile) {
                    $fileMeta = $downloadFileService->storeFile(
                        $uploadedFile,
                        $projectDirectory . '/var/uploads/',
                        $resource->getTitle()
                    );

                    $resource
                        ->setFilename($fileMeta['filename'])
                        ->setFilesize($fileMeta['filesize'])
                        ->setFiletype($fileMeta['filetype'])
                        ->setChecksum($fileMeta['checksum']);
                }
            }

            $resource->setAuthor($this->getCurrentUser());
            $resource->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($resource);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved successfully.');

            return $this->redirectToRoute('incp_resource_edit', [
                'type' => $request->attributes->getString('type'),
                'filename' => $resource->getId(),
            ]);
        }

        $additional = [];
        if ($resource instanceof Image) {
            try {
                $sizes = $resource->getImageProperties($projectDirectory . '/public/imgs/');
            } catch (FileNotFoundException $exception) {
                $this->addFlash('error', 'Associated image file could not be found');
            }
            $additional['channels'] = $sizes['channels'] ?? '';
            $additional['bits'] = $sizes['bits'] ?? '';
            $additional['limitKByte'] = Image::WARNING_FILESIZE;
            $additional['limitSize'] = Image::WARNING_DIMENSIONS;
        }

        $this->viewModel->page->type = $request->attributes->getString('type');
        $this->viewModel->page->title = sprintf('%s: %s', $type, $resource->getTitle());
        $this->viewModel->page->tab = $type;

        return $this->render('inadmin/page/resource/edit.html.twig', [
            'viewModel' => $this->viewModel,
            'additional' => $additional,
            'form' => $form->createView(),
            'resource' => $resource,
            'usages' => $usages,
        ]);
    }

    #[Route('/incp/resource/download/upload', name: 'incp_resource_upload_download', methods: ['POST', 'PUT'])]
    public function uploadDownload(
        Request $request,
        DownloadFileService $downloadFileService,
        DownloadRepository $downloadRepository,
        #[Autowire('%kernel.project_dir%/var/uploads/')] string $uploadDirectory
    ): JsonResponse {
        $downloadData = $request->request->all('download');
        $uploadedFileInput = null;

        if ($request->files->has('download')) {
            $fileBag = $request->files->get('download');
            if (is_array($fileBag)) {
                $uploadedFileInput = $fileBag['file'] ?? null;
            }
        }

        if (!$uploadedFileInput) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        }
        if (empty($downloadData['title'])) {
            return new JsonResponse(['error' => 'No title provided'], 400);
        }

        try {
            $checksum = $downloadFileService->createChecksum($uploadedFileInput);
            
            // Prevent duplicate file uploads
            if ($downloadRepository->findOneBy(['checksum' => $checksum])) {
                return new JsonResponse(['error' => 'Duplicate file found'], 400);
            }

            $fileMeta = $downloadFileService->storeFile(
                $uploadedFileInput,
                $uploadDirectory,
                $downloadData['title']
            );

            $download = new Download();
            $download
                ->setTitle($downloadData['title'])
                ->setDescription($downloadData['description'] ?? null)
                ->setFilesize($fileMeta['filesize'])
                ->setFiletype($fileMeta['filetype'])
                ->setFilename($fileMeta['filename'])
                ->setChecksum($fileMeta['checksum'])
                ->setAuthor($this->getCurrentUser());

            $this->entityManager->persist($download);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'id' => $download->getId()->toString(),
                'filename' => $fileMeta['filename'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/incp/resource/image/upload', name: 'incp_resource_upload_image', methods: ['POST', 'PUT'])]
    public function uploadImage(
        Request $request,
        ImageFileService $imageFileService,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory): JsonResponse
    {
        /** @var array{
         *     title: string,
         *     description?: string,
         *     altText?: string,
         *     optimise?: bool
         * } $imageData
         */
        $imageData = $request->request->all('image');
        $uploadedFileInput = null;
        if ($request->files->has('image')) {
            $imageBag = $request->files->get('image');

            if (is_array($imageBag)) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
                $uploadedFileInput = $imageBag['imageFile'] ?? null;
            }
        }

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
            if (false === $dimensions) {
                throw new \RuntimeException('Unable to read image dimensions.');
            }

            // Step 4: Generate checksum
            $checksum = $imageFileService->createChecksum($uploadedFile);

            // Step 4a: Check for duplicate checksum
            $existingImage = $this->entityManager->getRepository(Image::class)->findOneBy([
                'checksum' => $checksum,
            ]);
            if ($existingImage) {
                return new JsonResponse(['error' => 'Duplicate image found'], 400);
            }

            // Step 5: Create safe filename
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $title = '' !== trim($imageData['title'])
                ? $imageData['title']
                : $originalFilename;

            $safeFilename = strtolower(
                (string) $slugger->slug($title.'-'.uniqid()),
            );
            $newFilename = $safeFilename.'.'.$uploadedFile->guessExtension();

            $imageSize = $uploadedFile->getSize();
            $imageMimeType = $uploadedFile->getMimeType();

            // Step 6: Move file to storage directory
            $uploadedFile->move($imageDirectory, $newFilename);

            // Step 7: Create db record
            $image = new Image();
            $image
                ->setTitle($imageData['title'])
                ->setDescription($imageData['description'] ?? null)
                ->setAltText($imageData['altText'] ?? null)
                ->setFilesize($imageSize)
                ->setFiletype($imageMimeType ?? '')
                ->setFilename($newFilename)
                ->setChecksum($checksum)
                ->setDimensionX(intval($dimensions[0]))
                ->setDimensionY(intval($dimensions[1]));

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
            return new JsonResponse(['error' => 'File upload failed: '.$e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
