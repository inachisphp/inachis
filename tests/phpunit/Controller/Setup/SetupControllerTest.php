<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Setup;

use App\Controller\Setup\SetupController;
use App\Repository\UserRepository;
use App\Service\Page\ContentAggregator;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

class SetupControllerTest extends TestCase
{
    public function testStage1RedirectsIfSetup(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllCount'])
            ->getMock();
        $userRepository->expects($this->once())->method('getAllCount')->willReturn(0);
        $controller = new SetupController(
            $this->createMock(EntityManager::class),
            $this->createMock(Security::class),
            $this->createMock(Translator::class),
        );
        $result = $controller->stage1(new Request(), $userRepository);
    }


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
