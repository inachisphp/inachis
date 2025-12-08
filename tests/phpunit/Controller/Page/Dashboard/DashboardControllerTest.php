<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Url;

use App\Controller\Page\Dashboard\DashboardController;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $paginator = $this->createMock(Paginator::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->method('getAll')->willReturn($paginator);
        
        $result = $controller->default($request, $pageRepository);
        $this->assertEquals(
            'rendered:inadmin/page/dashboard/dashboard.html.twig',
            $result->getContent()
        );
    }
}
