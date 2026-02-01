<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Resource;

use Inachis\Controller\Page\Resource\ResourceController;
use Inachis\Entity\Image;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\DownloadRepository;
use Inachis\Repository\ImageRepository;
use Inachis\Repository\PageRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Service\Resource\ImageFileService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Translation\Translator;

class ResourceControllerTest extends WebTestCase
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
            'REQUEST_URI' => '/incc/resources/{type}/{offset}/{limit}'
        ]);
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $paginator = $this->createStub(Paginator::class);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('getFiltered')->willReturn($paginator);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createFormBuilder', 'generateUrl', 'render'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->expects($this->once())
            ->method('process')
            ->willReturn([
                'filters' => '',
                'offset' => '',
                'limit' => '',
                'sort' => '',
            ]);

        $result = $this->controller->list($request, $contentQueryParameters, $downloadRepository, $imageRepository);
        $this->assertEquals('rendered:inadmin/page/resource/list.html.twig', $result->getContent());
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
            'REQUEST_URI' => '/incc/resources/{type}/{filename}'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'render'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $filesystem = $this->createStub(Filesystem::class);
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $image = new Image();
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('findOneBy')->willReturn($image);
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('getPostsUsingImage')->willReturn($paginator);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('getSeriesUsingImage')->willReturn($paginator);
        $imageDirectory = '/tmp/';
        $result = $this->controller->edit($request, $filesystem, $downloadRepository, $imageRepository,
            $pageRepository, $seriesRepository, $imageDirectory);
        $this->assertEquals('rendered:inadmin/page/resource/edit.html.twig', $result->getContent());
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
            'REQUEST_URI' => '/incc/resources/{type}/{filename}'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_resource_list', ['type' => 'images'])
            ->willReturn(new RedirectResponse('/resources/images'));
        $filesystem = $this->createStub(Filesystem::class);
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $image = null;
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('findOneBy')->willReturn($image);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->never())->method('getPostsUsingImage');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->never())->method('getSeriesUsingImage');
        $imageDirectory = '/tmp/';
        $result = $this->controller->edit($request, $filesystem, $downloadRepository, $imageRepository,
            $pageRepository, $seriesRepository, $imageDirectory);
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
            'REQUEST_URI' => '/incc/resources/{type}/{filename}'
        ]);
        $image = (new Image())->setId(Uuid::uuid1());
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_resource_list', ['type' => 'images', ])
            ->willReturn(new RedirectResponse('/resources/images'));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('getData')->willReturn($image);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('exists')->willReturn(true);
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('findOneBy')->willReturn($image);
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('getPostsUsingImage')->willReturn($paginator);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('getSeriesUsingImage')->willReturn($paginator);
        $imageDirectory = '/tmp/';
        $result = $this->controller->edit($request, $filesystem, $downloadRepository, $imageRepository,
            $pageRepository, $seriesRepository, $imageDirectory);
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
            'REQUEST_URI' => '/incc/resources/{type}/{filename}'
        ]);
        $image = (new Image())->setId(Uuid::uuid1());
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_resource_edit', ['type' => 'images', 'filename' => $image->getId(), ])
            ->willReturn(new RedirectResponse('/resources/images'));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('getData')->willReturn($image);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('exists')->willReturn(true);
        $filesystem->expects($this->once())
            ->method('remove')
            ->willThrowException(new IOException('Failed to remove file.'));
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('findOneBy')->willReturn($image);
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('getPostsUsingImage')->willReturn($paginator);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('getSeriesUsingImage')->willReturn($paginator);
        $imageDirectory = '/tmp/';

        $result = $this->controller->edit($request, $filesystem, $downloadRepository, $imageRepository,
            $pageRepository, $seriesRepository, $imageDirectory);
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
            'REQUEST_URI' => '/incc/resources/{type}/{filename}'
        ]);
        $image = (new Image())->setId(Uuid::uuid1());
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_resource_edit', ['type' => 'images', 'filename' => $image->getId()])
            ->willReturn(new RedirectResponse('/resources/images'));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('getData')->willReturn($image);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);
        $filesystem = $this->createStub(Filesystem::class);
        $downloadRepository = $this->createStub(DownloadRepository::class);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->expects($this->once())->method('findOneBy')->willReturn($image);
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('getPostsUsingImage')->willReturn($paginator);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())->method('getSeriesUsingImage')->willReturn($paginator);
        $imageDirectory = '/tmp/';
        $result = $this->controller->edit($request, $filesystem, $downloadRepository, $imageRepository,
            $pageRepository, $seriesRepository, $imageDirectory);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/resources/images', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testUploadImageNoFile(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/resource/image/upload'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->never())->method('redirectToRoute');
        $imageFileService = $this->createStub(ImageFileService::class);
        $slugger = $this->createStub(SluggerInterface::class);
        $result = $this->controller->uploadImage($request, $imageFileService, $slugger, '/tmp');
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('{"error":"No file provided"}', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testUploadImageNoTitle(): void
    {
        $request = new Request([], [], [], [], [
            'image' => $this->createStub(UploadedFile::class),
        ], [
            'REQUEST_URI' => '/incc/resource/image/upload'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->never())->method('redirectToRoute');
        $imageFileService = $this->createStub(ImageFileService::class);
        $slugger = $this->createStub(SluggerInterface::class);
        $result = $this->controller->uploadImage($request, $imageFileService, $slugger, '/tmp');
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('{"error":"No title provided"}', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testUploadImage(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('test');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
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
            'REQUEST_URI' => '/incc/resource/image/upload'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->never())->method('redirectToRoute');
        $imageFileService = $this->createMock(ImageFileService::class);
        $imageFileService->expects($this->once())->method('convertHEICToJPEG')->willReturn($file);
        $imageFileService->expects($this->once())->method('createChecksum')->willReturn('test');
        $imageFileService->expects($this->once())->method('getImageDimensions')->willReturn([
            10,
            10,
        ]);
        $imageFileService->expects($this->once())->method('optimise')->willReturn($file);
        $slugger = $this->createMock(SluggerInterface::class);
        $slugger->expects($this->once())->method('slug')->willReturn(new UnicodeString('test'));
        $result = $this->controller->uploadImage($request, $imageFileService, $slugger, '/tmp');
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('OK', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testUploadImageFailed(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('test');
        $file->expects($this->once())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('getSize')->willReturn(1024);
        $file->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $file->expects($this->once())->method('move')->willThrowException(new FileException());
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
            'REQUEST_URI' => '/incc/resource/image/upload'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(ResourceController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $this->controller->expects($this->never())->method('redirectToRoute');
        $imageFileService = $this->createMock(ImageFileService::class);
        $imageFileService->expects($this->once())->method('convertHEICToJPEG')->willReturn($file);
        $imageFileService->expects($this->once())->method('createChecksum')->willReturn('test');
        $imageFileService->expects($this->once())->method('getImageDimensions')->willReturn([
            10,
            10,
        ]);
        $imageFileService->expects($this->once())->method('optimise')->willReturn($file);
        $slugger = $this->createMock(SluggerInterface::class);
        $slugger->expects($this->once())->method('slug')->willReturn(new UnicodeString('test'));
        $result = $this->controller->uploadImage($request, $imageFileService, $slugger, '/tmp');
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertStringContainsString('error', $result->getContent());
    }
}
