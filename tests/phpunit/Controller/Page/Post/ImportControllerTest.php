<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Post;

use Inachis\Controller\Page\Post\ImportController;
use Inachis\Service\Page\PageFileImportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

class ImportControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private Translator $translator;

    public function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(Translator::class);
    }

    public function testIndex(): void
    {
        $controller = $this->getMockBuilder(ImportController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $controller->index();
        $this->assertEquals('rendered:inadmin/page/post/import.html.twig', $result->getContent());
    }

    public function testProcess(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_OK);
        $request = new Request([], [], [], [], [
            'markdownFiles' => $file,
        ], [
            'REQUEST_URI' => '/incc/import'
        ]);
        $controller = $this->getMockBuilder(ImportController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['json', 'createFormBuilder'])
            ->getMock();
        $controller->expects($this->once())
            ->method('json')
            ->willReturn(new JsonResponse('success', 200));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('handleRequest');
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);
        $fileImportService = $this->createStub(PageFileImportService::class);

        $result = $controller->process($request, $fileImportService);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('"success"', $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testProcessUploadError(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);
        $request = new Request([], [], [], [], [
            'markdownFiles' => $file,
        ], [
            'REQUEST_URI' => '/incc/import'
        ]);
        $controller = $this->getMockBuilder(ImportController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['json', 'createFormBuilder'])
            ->getMock();
        $controller->expects($this->exactly(2))
            ->method('json')
            ->willReturn(new JsonResponse('error', 400));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('handleRequest');
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);
        $fileImportService = $this->createStub(PageFileImportService::class);

        $result = $controller->process($request, $fileImportService);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('"error"', $result->getContent());
        $this->assertEquals(400, $result->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testProcessProcessError(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_OK);
        $request = new Request([], [], [], [], [
            'markdownFiles' => $file,
        ], [
            'REQUEST_URI' => '/incc/import'
        ]);
        $controller = $this->getMockBuilder(ImportController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['json', 'createFormBuilder'])
            ->getMock();
        $controller->expects($this->exactly(2))
            ->method('json')
            ->willReturn(new JsonResponse('error', 409));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('handleRequest');
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);
        $fileImportService = $this->createMock(PageFileImportService::class);
        $fileImportService->expects($this->once())
            ->method('processFile')
            ->willReturn(409);

        $result = $controller->process($request, $fileImportService);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('"error"', $result->getContent());
        $this->assertEquals(409, $result->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testProcessNoFilesError(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/import'
        ]);
        $controller = $this->getMockBuilder(ImportController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['json', 'createFormBuilder'])
            ->getMock();
        $controller->expects($this->exactly(2))
            ->method('json')
            ->willReturn(new JsonResponse('error', 400));
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('handleRequest');
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);
        $fileImportService = $this->createStub(PageFileImportService::class);

        $result = $controller->process($request, $fileImportService);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('"error"', $result->getContent());
        $this->assertEquals(400, $result->getStatusCode());
    }
}
