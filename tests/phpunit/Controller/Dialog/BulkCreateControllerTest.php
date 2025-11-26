<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Dialog;

use App\Controller\Dialog\BulkCreateController;
use App\Entity\User;
use App\Service\Content\BulkCreatePost;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BulkCreateControllerTest extends WebTestCase
{
    protected BulkCreateController $controller;

    public function testContentList(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/bulkCreate/get'
        ]);

        $this->controller = $this->getMockBuilder(BulkCreateController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();
        $this->controller->method('getUser')->willReturn(new User());
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $this->controller->contentList($request);
        $this->assertEquals('rendered:inadmin/dialog/bulk-create.html.twig', $result->getContent());
    }

    public function testSaveContentBadRequest(): void
    {
        $request = new Request([], [
            'form' => [
                'startDate' => '01/11/2025',
                'endDate' => '07/11/2025',
                'tags' => ['test-tag'],
                'categories' => ['test-category'],
            ],
            'seriesId' => Uuid::uuid1()->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/bulkCreate/get'
        ]);
        $bulkCreatePost = $this->createMock(BulkCreatePost::class);
        $this->controller = $this->getMockBuilder(BulkCreateController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();
        $this->controller->method('getUser')->willReturn(new User());
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $this->controller->saveContent($request, $bulkCreatePost);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getStatusCode());
        $this->assertEquals('Title is required.', $result->getContent());
    }

    public function testSaveContentNoChange(): void
    {
        $request = new Request([], [
            'form' => [
                'title' => 'some title',
                'startDate' => '01/11/2025',
                'endDate' => '07/11/2025',
                'tags' => ['test-tag'],
                'categories' => ['test-category'],
            ],
            'seriesId' => Uuid::uuid1()->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/bulkCreate/get'
        ]);
        $bulkCreatePost = $this->createMock(BulkCreatePost::class);
        $this->controller = $this->getMockBuilder(BulkCreateController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();
        $this->controller->method('getUser')->willReturn(new User());
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $this->controller->saveContent($request, $bulkCreatePost);
        $this->assertEquals('No change', $result->getContent());
    }

    public function testSaveContent(): void
    {
        $request = new Request([], [
            'form' => [
                'title' => 'some title',
                'startDate' => '01/11/2025',
                'endDate' => '07/11/2025',
                'tags' => ['test-tag'],
                'categories' => ['test-category'],
            ],
            'seriesId' => Uuid::uuid1()->toString(),
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/bulkCreate/get'
        ]);
        $bulkCreatePost = $this->createMock(BulkCreatePost::class);
        $bulkCreatePost->method('create')->willReturn(7);
        $this->controller = $this->getMockBuilder(BulkCreateController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();
        $this->controller->method('getUser')->willReturn(new User());
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $this->controller->saveContent($request, $bulkCreatePost);
        $this->assertEquals('Saved', $result->getContent());
    }
}
