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
use Inachis\Entity\Media\{Download, Image};
use Inachis\Form\ResourceType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\{CategoryRepository, PageRepository, SeriesRepository};
use Inachis\Repository\Media\{DownloadRepository, ImageRepository};
use Inachis\Service\Content\ViewStateManager;
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
    #[Route("/incc/resources/{type}/{limit}/{offset}",
        name: "incc_resource_list",
        requirements: [
            "type" => "(images|downloads)",
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "limit" => 25, "offset" => 0, ],
        methods: [ "GET", "POST" ],
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        DownloadRepository $downloadRepository,
        ImageRepository $imageRepository,
        ViewStateManager $viewStateManager,
    ): Response {
        $typeClass = match($request->attributes->getString('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\') ?: '', 1);
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
                
        $params = $viewStateManager->load(
            $request,
            strtolower($type),
            new ViewStateDefaults(
                sort: 'title asc',
                view: 'grid',
            ),
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $viewStateManager->update(
                $request,
                strtolower($type),
                $params,
                $categoryRepository,
            );

            return $this->redirectToRoute('incc_resource_list', [
                'type' => $request->attributes->getString('type'),
                'limit' => $request->attributes->getInt('limit'),
                'offset' => $request->attributes->getInt('offset'),
            ]);
        }

        if ($repository instanceof ImageRepository && $request->query->getString('altText', '') === 'null') {
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

        $this->viewModel->page->title = $type . 's';
        $this->viewModel->page->type = strtolower($type) . 's';
        $this->viewModel->page->tab = strtolower($type);
        return $this->render('inadmin/page/resource/list.html.twig', [
            'viewModel' => $this->viewModel,
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'dataset' => $dataset,
            'form' => $form->createView(),
            'limitKByte' => Image::WARNING_FILESIZE,
            'limitSize' => Image::WARNING_DIMENSIONS,
            'query' => $params,
            'showUploadDialog' => $request->query->has('upload') && $request->query->getString('upload') === 'true',
        ]);
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
        WasteManagerService $wasteManagerService,
        #[Autowire('%kernel.project_dir%/public/imgs/')] string $imageDirectory
    ): Response {
//            "filename" => "[a-zA-Z0-9\-\_]\.(jpe?g|heic|png)",
        $typeClass = match ($request->attributes->getString('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\') ?: '', 1);
        $repository = match($type) {
            'Download' => $downloadRepository,
            default => $imageRepository,
        };
        $resource = $repository->findOneBy([
            'id' => $request->attributes->getString('filename'),
        ]);
        if (empty($resource)) {
            return $this->redirectToRoute(
                'incc_resource_list',
                [
                    'type' => $request->attributes->getString('type'),

                ],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
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
                $filename = $imageDirectory . $resource->getFilename();
                if ($resource instanceof Image &&
                    isset($usages['posts']) &&
                    empty($usages['posts']) &&
                    $usages['series']->count() === 0 &&
                    $filesystem->exists($filename)) {
                    try {
                        $wasteManagerService->sendToWaste($resource);
                        $repository->remove($resource);
                        $this->addFlash('success', 'Resource deleted.');
                        return $this->redirectToRoute(
                            'incc_resource_list',
                            [
                                'type' => $request->attributes->getString('type'),

                            ],
                            Response::HTTP_PERMANENTLY_REDIRECT
                        );
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to remove file.');
                        return $this->redirectToRoute(
                            'incc_resource_edit', [
                                'type' => $request->attributes->getString('type'),
                                'filename' => $resource->getId(),
                            ]
                        );
                    }
                }
            }
            $resource->setAuthor($this->getCurrentUser());
            $resource->setUpdatedAt(new DateTimeImmutable());
            $this->entityManager->persist($resource);
            $this->entityManager->flush();

            $this->addFlash('success', 'Content saved.');
            return $this->redirectToRoute(
                'incc_resource_edit', [
                    'type' => $request->attributes->getString('type'),
                    'filename' => $resource->getId(),
                ]
            );
        }

        $additional = [];
        if ($resource instanceof Image) {
            try {
                $sizes = $resource->getImageProperties($imageDirectory);
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
            if ($dimensions === false) {
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
            $title = trim($imageData['title']) !== ''
                ? $imageData['title']
                : $originalFilename;

            $safeFilename = strtolower(
                (string) $slugger->slug($title . '-' . uniqid())
            );
            $newFilename = $safeFilename . '.' . $uploadedFile->guessExtension();

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
            return new JsonResponse(['error' => 'File upload failed: ' . $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
