<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Controller\RssController;
use Inachis\Entity\Page;
use Inachis\Repository\PageRepository;
use Inachis\Repository\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class RssControllerTest extends TestCase
{
    public function testFeedActionRendersXml(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $params = $this->createMock(ParameterBagInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $wasteRepository = $this->createMock(WasteRepository::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $paginator = $this->createMock(Paginator::class);

        $params->method('get')->willReturnMap([
            ['kernel.project_dir', '/tmp'],
        ]);

        $paginator->method('getIterator')->willReturn(new \ArrayIterator([]));

        $pageRepository->expects($this->once())
            ->method('getFilteredOfTypeByPostDate')
            ->willReturn($paginator);

        $em->method('getRepository')
            ->with(Page::class)
            ->willReturn($pageRepository);

        $controller = $this->getMockBuilder(RssController::class)
            ->setConstructorArgs([$em, $params, $security, $translator, $wasteRepository])
            ->onlyMethods(['render'])
            ->getMock();

        $controller->setDefaults();

        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new Response('<rss></rss>', 200, ['Content-Type' => 'application/rss+xml']));

        $request = new Request();
        $response = $controller->feed($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<rss></rss>', $response->getContent());
        $this->assertSame('application/rss+xml', $response->headers->get('Content-Type'));
    }
}
