<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller;

use App\Controller\DefaultController;
use App\Entity\Page;
use App\Entity\Series;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class DefaultControllerTest extends WebTestCase
{
    private DefaultController $controller;
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->controller = new DefaultController($this->entityManager, $this->security);

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
        $uuid = Uuid::uuid1();
        $uuid2 = Uuid::uuid1();
        $uuid3 = Uuid::uuid1();
        $page1 = (new Page())->setId($uuid);
        $page2 = (new Page())->setId($uuid2);
        $page3 = (new Page())->setId($uuid3);
        $series = (new Series())->addItem($page1)->addItem($page3);
        $series->setFirstDate(new DateTime('now'))->setLastDate(new DateTime('now'));
        $seriesRepo = $this->getMockBuilder(EntityRepository::class)
            ->addMethods([ 'findOneByTitle', 'getAll' ])
            ->disableOriginalConstructor()
            ->getMock();
        $seriesRepo->expects($this->once())
            ->method('getAll')
            ->willReturn([ $series ]);
        $pageRepo = $this->getMockBuilder(EntityRepository::class)
            ->addMethods([ 'findOneByTitle', 'getAll' ])
            ->disableOriginalConstructor()
            ->getMock();
        $pageRepo->expects($this->once())
            ->method('getAll')
            ->with()
            ->willReturn([ $page1, $page2, $page3 ]);
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Series::class, $seriesRepo],
                [Page::class, $pageRepo],
            ]);
        $controller = $this->getMockBuilder(DefaultController::class)
            ->setConstructorArgs([$this->entityManager, $this->security])
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
            ->with('web/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));
        $response = $controller->homepage();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
