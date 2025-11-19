<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Series;

use App\Controller\Page\Series\SeriesWebController;
use App\Entity\Series;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeriesWebControllerTest extends WebTestCase
{
    private SeriesWebController $controller;
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    private TranslatorInterface $translator;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->controller = new SeriesWebController($this->entityManager, $this->security, $this->translator);

        $ref = new ReflectionClass($this->controller);
        foreach (['entityManager', 'security'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setValue($this->controller, $this->$prop);
        }
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn(null);
        $this->controller->setContainer($container);
    }

    public function testViewRendersTemplate(): void
    {
        $request = new Request();
        $series = $this->createMock(Series::class);
        $seriesRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepo->expects($this->once())
            ->method('getSeriesByYearAndUrl')
            ->with('2025', 'test')
            ->willReturn($series);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Series::class, $seriesRepo],
            ]);
        $controller = $this->getMockBuilder(SeriesWebController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
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
            ->with('web/series.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->view($request, '2025', 'test');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testViewThrowsNotFound(): void
    {
        $request = new Request();
        $seriesRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepo->expects($this->once())
            ->method('getSeriesByYearAndUrl')
            ->with('2025', 'test')
            ->willReturn(null);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Series::class, $seriesRepo]
            ]);
        $this->expectException(NotFoundHttpException::class);
        $this->controller->view($request, '2025', 'test');
    }
}
