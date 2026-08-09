<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\ChangePasswordController;
use Inachis\Entity\User\User;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
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
        $request = new Request(
            [],
            [
                'change_password' => [
                    'new_password' => 'NewSecurePassword123!',
                ],
            ],
            [
                'id' => 'test-user',
            ],
            [],
            [],
            [
                'REQUEST_URI' => '/incp/admin/test-user/change-password',
            ],
        );

        $user = new User('test-user');

        $security = $this->createMock(Security::class);
        $security->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        /** @var ChangePasswordController&MockObject $controller */
        $controller = $this->getMockBuilder(ChangePasswordController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'render'])
            ->getMock();

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn(new FormView());

        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Password updated.');

        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(
                static function (string $template, array $data): Response {
                    return new Response('rendered:'.$template);
                },
            );

        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $trustedDeviceManager->expects($this->once())
            ->method('removeAll')
            ->with($user);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'NewSecurePassword123!')
            ->willReturn('hashed-password');

        $passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'NewSecurePassword123!')
            ->willReturn(true);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test-user'])
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $controller->changePasswordTab(
            $request,
            $trustedDeviceManager,
            $passwordHasher,
            $userRepository,
        );

        $this->assertSame(
            'rendered:inadmin/page/admin/change-password.html.twig',
            $result->getContent(),
        );

        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertNotNull($user->getPasswordChangedAt());
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordTabThrowsExceptionWhenPasswordInvalid(): void
    {
        $request = new Request(
            [],
            [
                'change_password' => [
                    'new_password' => 'NewSecurePassword123!',
                ],
            ],
            [
                'id' => 'test-user',
            ],
            [],
            [],
            [
                'REQUEST_URI' => '/incp/admin/test-user/change-password',
            ],
        );

        $user = new User('test-user');

        $security = $this->createMock(Security::class);
        $security->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var ChangePasswordController&MockObject $controller */
        $controller = $this->getMockBuilder(ChangePasswordController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createForm'])
            ->getMock();

        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $trustedDeviceManager->expects($this->never())
            ->method('removeAll');

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'NewSecurePassword123!')
            ->willReturn('hashed-password');

        $passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'NewSecurePassword123!')
            ->willReturn(false);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test-user'])
            ->willReturn($user);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->expectException(AccessDeniedHttpException::class);

        $controller->changePasswordTab(
            $request,
            $trustedDeviceManager,
            $passwordHasher,
            $userRepository,
        );
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordTabThrowsExceptionWhenUserNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [
                'id' => 'unknown-user',
            ],
        );

        $security = $this->createStub(Security::class);

        $controller = new ChangePasswordController(
            $this->entityManager,
            $this->params,
            $security,
            $this->translator,
            $this->wasteRepository,
            $this->pageViewFactory,
            $this->requestStack,
        );

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'unknown-user'])
            ->willReturn(null);

        $this->expectException(AccessDeniedHttpException::class);

        $controller->changePasswordTab(
            $request,
            $this->createStub(TrustedDeviceManager::class),
            $this->createStub(UserPasswordHasherInterface::class),
            $userRepository,
        );
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordTabRejectsUsernameInPassword(): void
    {
        $request = new Request(
            [],
            [
                'change_password' => [
                    'new_password' => 'test-user-password',
                ],
            ],
            [
                'id' => 'test-user',
            ],
        );

        $user = new User('test-user');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var ChangePasswordController&MockObject $controller */
        $controller = $this->getMockBuilder(ChangePasswordController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createForm'])
            ->getMock();

        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class,
        );
        $passwordHasher->expects($this->never())
            ->method('hashPassword');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Your password cannot contain username.',
        );

        $controller->changePasswordTab(
            $request,
            $this->createStub(TrustedDeviceManager::class),
            $passwordHasher,
            $userRepository,
        );
    }
}
