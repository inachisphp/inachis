<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Dialog;

use Inachis\Controller\Dialog\ImageGalleryDialogController;
use Inachis\Repository\Media\ImageRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageGalleryDialogControllerTest extends WebTestCase
{
    public function testGetImageManagerList(): void
    {
        $controller = $this->getMockBuilder(ImageGalleryDialogController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $result = $controller->getImageManagerList();
        $this->assertEquals('rendered:inadmin/dialog/image-manager.html.twig', $result->getContent());
    }

    public function testGetImageList(): void
    {
        $controller = $this->getMockBuilder(ImageGalleryDialogController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/ax/imageManager/getImages/50/25'
        ]);
        $paginator = $this->createStub(Paginator::class);
        $imageRepository = $this->getMockBuilder(ImageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFiltered'])
            ->getMock();
        $imageRepository->expects($this->once())->method('getFiltered')->willReturn($paginator);
        $result = $controller->getImageList($request, $imageRepository);
        $this->assertEquals('rendered:inadmin/partials/gallery.html.twig', $result->getContent());
    }
}
