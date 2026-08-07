<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Resource;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Media\AbstractFile;
use Inachis\Entity\Media\Download;
use Inachis\Entity\Media\Image;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\ResourceType;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Media\DownloadRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Resource\DownloadFileService;
use Inachis\Service\Resource\ImageFileService;
use Inachis\Service\Resource\ResourceStorageProvider;
use Inachis\Service\Resource\ResourceUsageService;
use Inachis\Service\Waste\WasteManagerService;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $typePlural = $request->attributes->getString('type');
        $typeClass = 'downloads' === $typePlural ? Download::class : Image::class;
        $typeShort = 'downloads' === $typePlural ? 'Download' : 'Image';
        $typeSingular = strtolower($typeShort);

        $repository = 'Download' === $typeShort ?
            $downloadRepository :
            $imageRepository;

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('incp_resource_list', [
                'type' => strtolower($typePlural),
            ]))
            ->getForm();
        $form->handleRequest($request);

        $params = $viewStateManager->load(
            $request,
            $typePlural,
            new ViewStateDefaults(
                sort: 'title asc',
                view: Download::class === $typeClass ? 'table' : 'grid',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                $typePlural,
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incp_resource_list', [
                'type' => $typePlural,
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

        $this->viewModel->page->title = $typeShort.'s';
        $this->viewModel->page->type = $typePlural;
        $this->viewModel->page->tab = $typeSingular;

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
        ResourceStorageProvider $storageProvider,
        ResourceUsageService $usageService,
        WasteManagerService $wasteManagerService,
    ): Response {
        $typePlural = $request->attributes->getString('type');
        $typeClass = 'downloads' === $typePlural ? Download::class : Image::class;
        $typeShort = 'downloads' === $typePlural ? 'Download' : 'Image';
        $typeSingular = strtolower($typeShort);

        $repository = 'Download' === $typeShort ? $downloadRepository : $imageRepository;

        $filenameParam = $request->attributes->getString('filename');
        if ('new' === $filenameParam) {
            $resource = new $typeClass();
        } else {
            $resource = $repository->findOneBy([
                'id' => $filenameParam,
            ]);
            if (empty($resource)) {
                return $this->redirectToRoute(
                    'incp_resource_list',
                    [
                        'type' => $typePlural,
                    ],
                    Response::HTTP_PERMANENTLY_REDIRECT,
                );
            }
        }

        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        $usages = $usageService->getUsages($resource);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AbstractFile $resource */
            $resource = $form->getData();

            if (isset($request->request->all('resource')['delete'])) {
                $filePath = $storageProvider->getFullPath($resource);

                if (!$usageService->isFileInUse($resource)) {
                    try {
                        if (!$filesystem->exists($filePath)) {
                            $this->addFlash('error', 'The file for this resource does not exist on disk and will not be recoverable.');
                        } else {
                            $wasteManagerService->sendToWaste($resource);
                        }
                        $repository->remove($resource);
                        $this->addFlash('success', 'Resource deleted.');

                        return $this->redirectToRoute(
                            'incp_resource_list',
                            ['type' => $typePlural],
                            Response::HTTP_PERMANENTLY_REDIRECT,
                        );
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to remove resource: '.$e->getMessage());

                        return $this->redirectToRoute(
                            'incp_resource_edit',
                            [
                                'type' => $typePlural,
                                'filename' => $resource->getId(),
                            ],
                        );
                    }
                } else {
                    $this->addFlash('error', 'Cannot remove file because it is currently in use.');
                }
            }

            if ($resource instanceof Download && $form->has('file')) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $uploadedFile */
                $uploadedFile = $form->get('file')->getData();

                if ($uploadedFile) {
                    $downloadFileService->replaceFile($resource, $uploadedFile);
                }
            }

            $resource->setAuthor($this->getCurrentUser());
            $resource->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($resource);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('%s saved successfully.', $typeShort));

            return $this->redirectToRoute('incp_resource_edit', [
                'type' => $request->attributes->getString('type'),
                'filename' => $resource->getId(),
            ]);
        }

        $additional = [];
        if ($resource instanceof Image && null !== $resource->getId()) {
            try {
                $sizes = $resource->getImageProperties($storageProvider->getStorageDirectory($resource));
                $additional['channels'] = $sizes['channels'] ?? '';
                $additional['bits'] = $sizes['bits'] ?? '';
            } catch (FileNotFoundException $exception) {
                $this->addFlash('error', 'Associated image file could not be found');
            }
            $additional['limitKByte'] = Image::WARNING_FILESIZE;
            $additional['limitSize'] = Image::WARNING_DIMENSIONS;
        }

        $this->viewModel->page->type = $typePlural;
        $this->viewModel->page->title = sprintf('%s: %s', $typeShort, $resource->getTitle());
        $this->viewModel->page->tab = $typeSingular;

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
            $download = $downloadFileService->createFromUpload(
                $uploadedFileInput,
                $downloadData['title'],
                $downloadData['description'] ?? null,
                $this->getCurrentUser(),
            );

            return new JsonResponse([
                'success' => true,
                'id' => $download->getId()?->toString(),
                'filename' => $download->getFilename(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/incp/resource/image/upload', name: 'incp_resource_upload_image', methods: ['POST', 'PUT'])]
    public function uploadImage(
        Request $request,
        ImageFileService $imageFileService,
    ): JsonResponse {
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
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null */
                $uploadedFileInput = $imageBag['imageFile'] ?? null;
            }
        }

        if (!$uploadedFileInput) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        } elseif (empty($imageData['title'])) {
            return new JsonResponse(['error' => 'No title provided'], 400);
        }

        try {
            $image = $imageFileService->createFromUpload(
                $uploadedFileInput,
                $imageData['title'],
                $imageData['description'] ?? null,
                $imageData['altText'] ?? null,
                !empty($imageData['optimise']),
                $this->getCurrentUser(),
            );

            return new JsonResponse([
                'success' => true,
                'filename' => $image->getFilename(),
                'checksum' => $image->getChecksum(),
                'dimensions' => [
                    'width' => $image->getDimensionX(),
                    'height' => $image->getDimensionY(),
                ],
            ]);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'File upload failed: '.$e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
