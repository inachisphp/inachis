<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\ChangePasswordController;
use Inachis\Entity\User\User;
use Inachis\Repository\UserRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testChangePasswordTab(): void
    {
        $request = new Request([], [
            'change_password' => [
                'new_password' => 'testpassword',
            ],
        ], [
            'id' => Uuid::uuid1(),
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/{id}/change-password'
        ]);
        $user = (new User('test-user'))->setId(Uuid::uuid1());
        $security = $this->createMock(Security::class);
        $security->expects($this->atLeastOnce())->method('getUser')->willReturn($user);

        /** @var ChangePasswordController&MockObject $controller */
        $controller = $this->getMockBuilder(ChangePasswordController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $controller->expects($this->once())->method('createForm')->willReturn($form);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())->method('isPasswordValid')->willReturn(true);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn($user);

        $result = $controller->changePasswordTab($request, $passwordHasher, $userRepository);
        $this->assertEquals('rendered:inadmin/page/admin/change-password.html.twig', $result->getContent());
    }

    public function testChangePasswordTabThrowsException(): void
    {
        $request = new Request([], [
            'change_password' => [
                'new_password' => 'testpassword',
            ],
        ], [
            'id' => Uuid::uuid1(),
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/{id}/change-password'
        ]);
        $user = (new User('test-user'))->setId(Uuid::uuid1());
        $security = $this->createMock(Security::class);
        $security->expects($this->atLeastOnce())->method('getUser')->willReturn($user);

        /** @var ChangePasswordController&MockObject $controller */
        $controller = $this->getMockBuilder(ChangePasswordController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $controller->expects($this->once())->method('createForm')->willReturn($form);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())->method('isPasswordValid')->willReturn(false);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn($user);

        $this->expectException(AccessDeniedHttpException::class);
        $controller->changePasswordTab($request, $passwordHasher, $userRepository);
    }

    public function testCalculatePasswordStrength(): void
    {
        $request = new Request([], [
            'password' => 'Testpa$$word123',
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/calculate-password-strength'
        ]);
        $controller = new ChangePasswordController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
            $this->wasteRepository
        );

        $result = $controller->calculatePasswordStrength($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(2, $result->getContent());
    }
}
