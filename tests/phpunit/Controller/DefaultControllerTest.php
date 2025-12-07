<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller;

use App\Controller\DefaultController;
use App\Service\Page\ContentAggregator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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

        $controller->method('render')
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
