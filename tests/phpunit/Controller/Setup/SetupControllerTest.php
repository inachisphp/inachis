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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

class SetupControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testStage1RedirectsIfSetup(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllCount'])
            ->getMock();
        $userRepository->expects($this->once())->method('getAllCount')->willReturn(5);
        $controller = $this->getMockBuilder(SetupController::class)
            ->setConstructorArgs([
                $this->createMock(EntityManager::class),
                $this->createMock(Security::class),
                $this->createMock(Translator::class),
            ])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $controller
            ->method('redirectToRoute')
            ->with('incc_dashboard')
            ->willReturn(new RedirectResponse('/'));
        $result = $controller->stage1($userRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/', $result->headers->get('Location'));
    }

    /**
     * @throws Exception
     */
    public function testStage1(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllCount'])
            ->getMock();
        $userRepository->expects($this->once())->method('getAllCount')->willReturn(0);
        $controller = $this->getMockBuilder(SetupController::class)
            ->setConstructorArgs([
                $this->createMock(EntityManager::class),
                $this->createMock(Security::class),
                $this->createMock(Translator::class),
            ])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $controller->stage1($userRepository);
        $this->assertEquals('rendered:setup/stage-1.html.twig', $result->getContent());
    }
}
