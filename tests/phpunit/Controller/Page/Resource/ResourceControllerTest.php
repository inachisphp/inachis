<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Resource;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Controller\Page\Resource\ResourceController;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Form\ResourceType;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\DownloadRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Repository\User\UserViewStateRepository;
use Inachis\Service\Ai\AiVisionManager;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Resource\DownloadFileService;
use Inachis\Service\Resource\ImageFileService;
use Inachis\Service\Resource\ResourceStorageProvider;
use Inachis\Service\Resource\ResourceUsageService;
use Inachis\Service\Waste\WasteManagerService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ResourceControllerTest extends InachisControllerTestCase
{
    protected ResourceController $controller;

    /**
     * @throws Exception
     */
    public function testList(): void
    {
        $request = new Request([], [], [
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{offset}/{limit}',
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $downloadRepository = $this->createStub(DownloadRepository::class);
        $paginator = $this->createStub(Paginator::class);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('getFiltered')
            ->willReturn($paginator);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createFormBuilder', 'generateUrl', 'render'])
            ->getMock();

        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });

        $result = $this->controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $downloadRepository,
            $imageRepository,
            new ViewStateManager(
                $this->createStub(Security::class),
                $this->createStub(UserViewStateRepository::class),
            ),
        );

        $this->assertEquals(
            'rendered:inadmin/page/resource/list.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testEdit(): void
    {
        $request = new Request([], [], [
            'filename' => Uuid::uuid1(),
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{filename}',
        ]);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'render'])
            ->getMock();

        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });

        $image = new Image();

        $filesystem = $this->createStub(Filesystem::class);
        $downloadRepository = $this->createStub(DownloadRepository::class);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($image);

        $usageService = $this->createMock(ResourceUsageService::class);
        $usageService->expects($this->once())
            ->method('getUsages')
            ->with($image)
            ->willReturn([]);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(ResourceType::class, $image)
            ->willReturn($form);

        $result = $this->controller->edit(
            $request,
            $filesystem,
            $this->createStub(DownloadFileService::class),
            $downloadRepository,
            $imageRepository,
            $this->createStub(AiVisionManager::class),
            $this->createStub(ResourceStorageProvider::class),
            $usageService,
            $this->createStub(WasteManagerService::class),
        );

        $this->assertEquals(
            'rendered:inadmin/page/resource/edit.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testEditEmptyResource(): void
    {
        $request = new Request([], [], [
            'filename' => Uuid::uuid1(),
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{filename}',
        ]);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'redirectToRoute'])
            ->getMock();

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_resource_list', ['type' => 'images'])
            ->willReturn(new RedirectResponse('/resources/images'));

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->never())
            ->method('getPostsUsingImage');

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->never())
            ->method('getSeriesUsingImage');

        $result = $this->controller->edit(
            $request,
            $this->createStub(Filesystem::class),
            $this->createStub(DownloadFileService::class),
            $this->createStub(DownloadRepository::class),
            $imageRepository,
            $this->createStub(AiVisionManager::class),
            $this->createStub(ResourceStorageProvider::class),
            $this->createStub(ResourceUsageService::class),
            $this->createStub(WasteManagerService::class),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/resources/images', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testEditRemove(): void
    {
        $request = new Request([], [
            'resource' => [
                'delete' => '',
            ],
        ], [
            'filename' => Uuid::uuid1(),
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{filename}',
        ]);

        $image = (new Image())->setId(Uuid::uuid1());

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'generateUrl',
                'getCurrentUser',
                'redirectToRoute',
            ])
            ->getMock();

        $this->controller->method('getCurrentUser')
            ->willReturn(new User());

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_resource_list', ['type' => 'images'])
            ->willReturn(new RedirectResponse('/resources/images'));

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($image);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($image);

        $usageService = $this->createMock(ResourceUsageService::class);
        $usageService->expects($this->once())
            ->method('getUsages')
            ->willReturn([]);
        $usageService->expects($this->once())
            ->method('isFileInUse')
            ->willReturn(false);

        $storageProvider = $this->createMock(ResourceStorageProvider::class);
        $storageProvider->expects($this->once())
            ->method('getFullPath')
            ->willReturn('/tmp/test.jpg');

        $wasteManagerService = $this->createMock(WasteManagerService::class);
        $wasteManagerService->expects($this->once())
            ->method('sendToWaste');

        $result = $this->controller->edit(
            $request,
            $filesystem,
            $this->createStub(DownloadFileService::class),
            $this->createStub(DownloadRepository::class),
            $imageRepository,
            $this->createStub(AiVisionManager::class),
            $storageProvider,
            $usageService,
            $wasteManagerService,
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/resources/images', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testEditRemoveFailed(): void
    {
        $request = new Request([], [
            'resource' => [
                'delete' => '',
            ],
        ], [
            'filename' => Uuid::uuid1(),
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{filename}',
        ]);

        $image = (new Image())->setId(Uuid::uuid1());

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'getCurrentUser',
                'redirectToRoute',
            ])
            ->getMock();

        $this->controller->method('getCurrentUser')
            ->willReturn(new User());

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_resource_edit', [
                'type' => 'images',
                'filename' => $image->getId(),
            ])
            ->willReturn(new RedirectResponse('/resources/images'));

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($image);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($image);

        $usageService = $this->createMock(ResourceUsageService::class);
        $usageService->expects($this->once())
            ->method('getUsages')
            ->willReturn([]);
        $usageService->expects($this->once())
            ->method('isFileInUse')
            ->willReturn(false);

        $storageProvider = $this->createMock(ResourceStorageProvider::class);
        $storageProvider->expects($this->once())
            ->method('getFullPath')
            ->willReturn('/tmp/test.jpg');

        $wasteManagerService = $this->createMock(WasteManagerService::class);
        $wasteManagerService->expects($this->once())
            ->method('sendToWaste')
            ->willThrowException(new \Exception('Failed to remove file.'));

        $result = $this->controller->edit(
            $request,
            $filesystem,
            $this->createStub(DownloadFileService::class),
            $this->createStub(DownloadRepository::class),
            $imageRepository,
            $this->createStub(AiVisionManager::class),
            $storageProvider,
            $usageService,
            $wasteManagerService,
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/resources/images', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testEditSave(): void
    {
        $request = new Request([], [], [
            'filename' => Uuid::uuid1(),
            'type' => 'images',
        ], [], [], [
            'REQUEST_URI' => '/incp/resources/{type}/{filename}',
        ]);

        $image = (new Image())->setId(Uuid::uuid1());

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'getCurrentUser',
                'redirectToRoute',
            ])
            ->getMock();

        $this->controller->method('getCurrentUser')
            ->willReturn(new User());

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_resource_edit', [
                'type' => 'images',
                'filename' => $image->getId(),
            ])
            ->willReturn(new RedirectResponse('/resources/images'));

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($image);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($image);

        $usageService = $this->createMock(ResourceUsageService::class);
        $usageService->expects($this->once())
            ->method('getUsages')
            ->willReturn([]);

        $result = $this->controller->edit(
            $request,
            $this->createStub(Filesystem::class),
            $this->createStub(DownloadFileService::class),
            $this->createStub(DownloadRepository::class),
            $imageRepository,
            $this->createStub(AiVisionManager::class),
            $this->createStub(ResourceStorageProvider::class),
            $usageService,
            $this->createStub(WasteManagerService::class),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/resources/images', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testUploadImageNoFile(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/resource/image/upload',
        ]);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();

        $this->controller->expects($this->never())
            ->method('redirectToRoute');

        $result = $this->controller->uploadImage(
            $request,
            $this->createStub(ImageFileService::class),
        );

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals(
            '{"error":"No file provided"}',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testUploadImageNoTitle(): void
    {
        $request = new Request([], [], [], [], [
            'image' => [
                'imageFile' => $this->createStub(UploadedFile::class),
            ],
        ], [
            'REQUEST_URI' => '/incp/resource/image/upload',
        ]);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();

        $this->controller->expects($this->never())
            ->method('redirectToRoute');

        $result = $this->controller->uploadImage(
            $request,
            $this->createStub(ImageFileService::class),
        );

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals(
            '{"error":"No title provided"}',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testUploadImage(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $request = new Request([], [
            'image' => [
                'title' => 'test-image',
                'description' => '',
                'altText' => '',
                'optimise' => 'true',
            ],
        ], [], [], [
            'image' => [
                'imageFile' => $file,
            ],
        ], [
            'REQUEST_URI' => '/incp/resource/image/upload',
        ]);

        $image = (new Image())
            ->setFilename('test.jpg')
            ->setChecksum('test')
            ->setDimensionX(10)
            ->setDimensionY(10);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['getCurrentUser'])
            ->getMock();

        $this->controller->method('getCurrentUser')
            ->willReturn(new User());

        $imageFileService = $this->createMock(ImageFileService::class);
        $imageFileService->expects($this->once())
            ->method('createFromUpload')
            ->with(
                $file,
                'test-image',
                '',
                '',
                true,
                $this->isInstanceOf(User::class),
            )
            ->willReturn($image);

        $result = $this->controller->uploadImage(
            $request,
            $imageFileService,
        );

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);

        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('test.jpg', $data['filename']);
        $this->assertSame('test', $data['checksum']);
        $this->assertSame(10, $data['dimensions']['width']);
        $this->assertSame(10, $data['dimensions']['height']);
    }

    /**
     * @throws Exception
     */
    public function testUploadImageFailed(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $request = new Request([], [
            'image' => [
                'title' => 'test-image',
                'description' => '',
                'altText' => '',
                'optimise' => 'true',
            ],
        ], [], [], [
            'image' => [
                'imageFile' => $file,
            ],
        ], [
            'REQUEST_URI' => '/incp/resource/image/upload',
        ]);

        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['getCurrentUser'])
            ->getMock();

        $this->controller->method('getCurrentUser')
            ->willReturn(new User());

        $imageFileService = $this->createMock(ImageFileService::class);
        $imageFileService->expects($this->once())
            ->method('createFromUpload')
            ->willThrowException(new FileException('Upload failed.'));

        $result = $this->controller->uploadImage(
            $request,
            $imageFileService,
        );

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('File upload failed', $data['error']);
    }
}
