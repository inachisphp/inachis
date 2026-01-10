<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Setup;

use Inachis\Controller\Setup\SetupController;
use Inachis\Repository\UserRepository;
use Inachis\Service\Page\ContentAggregator;
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
                $this->createStub(EntityManager::class),
                $this->createStub(Security::class),
                $this->createStub(Translator::class),
            ])
            ->onlyMethods(['redirectToRoute'])
            ->getMock();
        $controller->expects($this->once())
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
                $this->createStub(EntityManager::class),
                $this->createStub(Security::class),
                $this->createStub(Translator::class),
            ])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $controller->stage1($userRepository);
        $this->assertEquals('rendered:setup/stage-1.html.twig', $result->getContent());
    }

    public function testGetErrors(): void
    {
        $controller = new SetupController(
            $this->createStub(EntityManager::class),
            $this->createStub(Security::class),
            $this->createStub(Translator::class),
        );
        $this->assertEmpty($controller->getErrors());
    }

    public function testAddAndGetError(): void
    {
        $controller = new SetupController(
            $this->createStub(EntityManager::class),
            $this->createStub(Security::class),
            $this->createStub(Translator::class),
        );
        $controller->addError('test', 'Something went wrong');
        $this->assertEquals('Something went wrong', $controller->getError('test'));
    }
}
