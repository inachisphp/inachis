<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Admin;

use App\Controller\Page\Admin\AdminProfileController;
use App\Model\ContentQueryParameters;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminProfileControllerTest extends WebTestCase
{
    protected AdminProfileController $controller;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->controller = $this->getMockBuilder(AdminProfileController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $formBuilder = $this->createMock(FormBuilder::class);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

        parent::setUp();
    }
    public function testList(): void
    {
        $request = new Request([], [], [
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/list/50/25'
        ]);
        $userRepository = $this->createMock(UserRepository::class);
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->method('process')->willReturn([
            'filters' => [],
            'offset' => 50,
            'limit' => 25,
        ]);
        $result = $this->controller->list($request, $userRepository, $contentQueryParameters);
        $this->assertEquals('rendered:inadmin/page/admin/list.html.twig', $result->getContent());
    }
}
