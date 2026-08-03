<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller;

use Inachis\Controller\DefaultController;
use Inachis\Service\Content\Page\ContentAggregator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends AbstractWebControllerTestCase
{
    public function testHomepageRendersWithContent(): void
    {
        $mockContent = [
            '20240101' => 'test value',
        ];

        $contentProvider = $this->createMock(ContentAggregator::class);
        $contentProvider->expects($this->once())
            ->method('getHomepageContent')
            ->willReturn($mockContent);

        $controller = $this->getMockBuilder(DefaultController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->pageViewFactory,
                $this->params,
                $this->security,
                $this->translator,
            ])
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with(
                'web/pages/homepage.html.twig',
                $this->callback(function (array $vars) use ($mockContent) {
                    $this->assertArrayHasKey('viewModel', $vars);
                    $this->assertSame($mockContent, $vars['content']);

                    return true;
                })
            )
            ->willReturn(new Response('OK'));

        $response = $controller->homepage($contentProvider);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('OK', $response->getContent());
    }

    public function testHealthReturnsOkResponse(): void
    {
        /** @var DefaultController $controller */
        $controller = $this->createController(DefaultController::class);

        $response = $controller->health();

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('time', $data);
        $this->assertIsInt($data['time']);
    }
}
