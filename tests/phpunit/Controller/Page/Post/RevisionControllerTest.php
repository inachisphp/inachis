<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Post;

use App\Controller\Page\Post\RevisionController;
use App\Repository\PageRepository;
use App\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Translator;

class RevisionControllerTest extends WebTestCase
{
    protected RevisionController $controller;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(Translator::class);
        $this->controller = $this->getMockBuilder(RevisionController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods([])
            ->getMock();
    }

    public function testDiffEmptyRevision()
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/page/diff/{id}'
        ]);
        $pageRepository = $this->createMock(PageRepository::class);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->expectException(NotFoundHttpException::class);

        $this->controller->diff($request, $pageRepository, $revisionRepository);
    }
}
