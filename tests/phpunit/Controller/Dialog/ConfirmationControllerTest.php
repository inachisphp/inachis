<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Dialog;

use Inachis\Controller\Dialog\ConfirmationController;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testContentList(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/ax/confirmation/get'
        ]);
        $controller = $this->getMockBuilder(ConfirmationController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });

        $result = $controller->contentList($request);
        $this->assertEquals('rendered:inadmin/dialog/confirmation.html.twig', $result->getContent());
    }
}
