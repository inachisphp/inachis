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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testDefault(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/'
        ]);
        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $paginator = $this->createStub(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->atLeastOnce())->method('getAll')->willReturn($paginator);
        
        $result = $controller->default(
            $request,
            $this->createStub(AnalyticsProviderInterface::class),
            $this->createStub(ImageRepository::class),
            $pageRepository,
            $this->createStub(SeriesRepository::class),
        );
        $this->assertEquals(
            'rendered:inadmin/page/dashboard/dashboard.html.twig',
            $result->getContent()
        );
    }
}
