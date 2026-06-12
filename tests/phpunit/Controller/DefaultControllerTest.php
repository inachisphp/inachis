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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends TestCase
{
    public function testHomepageRendersWithContent(): void
    {
        $mockContent = [
            '20240101' => 'test value'
        ];
        $contentProvider = $this->createMock(ContentAggregator::class);
        $contentProvider->expects($this->once())
            ->method('getHomepageContent')
            ->willReturn($mockContent);

        $controller = $this->getMockBuilder(DefaultController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with(
                'web/pages/homepage.html.twig',
                ['content' => $mockContent]
            )
            ->willReturn(new Response('OK'));

        $response = $controller->homepage($contentProvider);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('OK', $response->getContent());
    }
}
