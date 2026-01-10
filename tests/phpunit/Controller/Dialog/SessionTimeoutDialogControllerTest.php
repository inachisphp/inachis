<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Dialog;

use App\Controller\Dialog\SessionTimeoutDialogController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class SessionTimeoutDialogControllerTest extends WebTestCase
{
    protected SessionTimeoutDialogController $controller;

    public function setUp(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $this->controller = new SessionTimeoutDialogController($entityManager, $security, $translator);
        parent::setUp();
    }
    public function testKeepAlive(): void
    {
        $result = $this->controller->keepAlive();
        $result = json_decode($result->getContent());
        $this->assertObjectHasProperty('time', $result);
        preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/', $result->time, $matches);
        $this->assertNotEmpty($matches);
    }

    public function testShowDialog(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/sessionTimeout/get'
        ]);
        $this->controller = $this->getMockBuilder(SessionTimeoutDialogController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();
        $this->controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $this->assertEquals(
            'rendered:inadmin/dialog/session_timeout.html.twig',
            $this->controller->showDialog($request)->getContent()
        );
    }
}
