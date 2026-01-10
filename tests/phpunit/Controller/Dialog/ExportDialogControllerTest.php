<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Dialog;

use Inachis\Controller\Dialog\ExportDialogController;
use Inachis\Entity\Page;
use Inachis\Repository\PageRepository;
use ArrayIterator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ExportDialogControllerTest extends WebTestCase
{
    protected ExportDialogController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->getMockBuilder(ExportDialogController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $this->controller->expects($this->atLeast(0))->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
    }

    public function testExport(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/export/get'
        ]);

        $result = $this->controller->export($request);

        $this->assertEquals('rendered:inadmin/dialog/export.html.twig', $result->getContent());
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testPerformExportNoneSelected(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/export/output'
        ]);
        $pageRepository = $this->createStub(PageRepository::class);
        $serializer = $this->createStub(SerializerInterface::class);

        $result = $this->controller->performExport($request, $serializer, $pageRepository);

        $this->assertEmpty($result->getContent());
        $this->assertEquals(Response::HTTP_EXPECTATION_FAILED, $result->getStatusCode());
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testPerformExportPostsNotFound(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'export_format' => 'json',
            'postId' => [ $uuid->toString(), ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/export/output'
        ]);
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([]));

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getFilteredIds')
            ->willReturn($paginator);
        $serializer = $this->createStub(SerializerInterface::class);

        $result = $this->controller->performExport($request, $serializer, $pageRepository);
        $this->assertEmpty($result->getContent());
        $this->assertEquals(Response::HTTP_EXPECTATION_FAILED, $result->getStatusCode());
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testPerformExportJson(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'export_categories' =>'true',
            'export_format' => 'json',
            'export_name' => 'test',
            'export_tags' =>'true',
            'postId' => [ $uuid->toString(), ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/export/output'
        ]);
        $result = $this->getPerformExportResult($request, $uuid);

        $this->assertEquals('"test-page"', $result->getContent());
        $this->assertEquals('application/json', $result->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment; filename=test',
            $result->headers->get('content-disposition')
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function testPerformExportXml(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'export_categories' =>'true',
            'export_format' => 'xml',
            'export_name' => 'test',
            'export_tags' =>'true',
            'postId' => [ $uuid->toString(), ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/export/output'
        ]);
        $result = $this->getPerformExportResult($request, $uuid);

        $this->assertStringContainsString('<response>test-page</response>', $result->getContent());
        $this->assertEquals('text/xml', $result->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment; filename=test',
            $result->headers->get('content-disposition')
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    private function getPerformExportResult(Request $request, UuidInterface $uuid): Response
    {
        $post = (new Page('test-page'))->setId($uuid);
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$post, $post]));

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getFilteredIds')
            ->willReturn($paginator);
        $serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['normalize'])
            ->getMock();
        $serializer->expects($this->once())
            ->method('normalize')
            ->willReturn('test-page');

        return $this->controller->performExport($request, $serializer, $pageRepository);
    }
}
