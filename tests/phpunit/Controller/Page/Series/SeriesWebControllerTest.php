<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Series;

use Inachis\Controller\Page\Series\SeriesWebController;
use Inachis\Entity\Content\Series;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SeriesWebControllerTest extends InachisControllerTestCase
{
    private SeriesWebController $controller;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new SeriesWebController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
        );

        $ref = new ReflectionClass($this->controller);
        foreach (['entityManager', 'security'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setValue($this->controller, $this->$prop);
        }
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn(null);
        $this->controller->setContainer($container);
    }

    public function testViewRendersTemplate(): void
    {
        $series = $this->createStub(Series::class);
        $seriesRepository = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepository->expects($this->once())
            ->method('getPublicSeriesByYearAndUrl')
            ->with('2025', 'test')
            ->willReturn($series);
        $this->entityManager->expects($this->never())
            ->method('getRepository')
            ->willReturnMap([
                [Series::class, $seriesRepository],
            ]);
        $controller = $this->getMockBuilder(SeriesWebController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $ref = new ReflectionClass($controller);
        foreach (['entityManager', 'security'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $property = $ref->getProperty($prop);
                $property->setValue($controller, $this->$prop);
            }
        }
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/series.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->view($seriesRepository, '2025', 'test');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testViewThrowsNotFound(): void
    {
        $seriesRepository = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepository->expects($this->once())
            ->method('getPublicSeriesByYearAndUrl')
            ->with('2025', 'test')
            ->willReturn(null);
        $this->entityManager->expects($this->never())
            ->method('getRepository')
            ->willReturnMap([
                [Series::class, $seriesRepository]
            ]);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->view($seriesRepository, '2025', 'test');
    }
}
