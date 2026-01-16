<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Dashboard;

use Inachis\Controller\Page\Dashboard\DashboardController;
use Inachis\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardControllerTest extends WebTestCase
{
    /**
     * @throws Exception
     */
    public function testDefault(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/'
        ]);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->atLeastOnce())->method('getAll')->willReturn($paginator);
        
        $result = $controller->default($request, $pageRepository);
        $this->assertEquals(
            'rendered:inadmin/page/dashboard/dashboard.html.twig',
            $result->getContent()
        );
    }
}
