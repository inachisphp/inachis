<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Dialog;

use App\Controller\Dialog\ConfirmationController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationControllerTest extends WebTestCase
{
    /**
     * @throws Exception
     */
    public function testContentList(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/confirmation/get'
        ]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(ConfirmationController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });

        $result = $controller->contentList($request);
        $this->assertEquals('rendered:inadmin/dialog/confirmation.html.twig', $result->getContent());
    }
}
