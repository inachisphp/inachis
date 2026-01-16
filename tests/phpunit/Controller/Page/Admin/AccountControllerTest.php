<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;


use Inachis\Controller\Page\Admin\AccountController;
use Inachis\Entity\PasswordResetRequest;
use Inachis\Entity\User;
use Inachis\Repository\PasswordResetRequestRepository;
use Inachis\Repository\UserRepository;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\Translator;

class AccountControllerTest extends WebTestCase
{
    /** @var AccountController&MockObject */
    protected AccountController $controller;

    public function setUp(): void
    {
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(Translator::class);
        $this->controller = $this->getMockBuilder(AccountController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods([
                'addFlash', 'createForm', 'createFormBuilder', 'redirectIfAuthenticatedOrNoAdmins',
                'redirectToRoute', 'render'
            ])
            ->getMock();
        $this->controller->expects($this->atLeast(0))->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
    }

    public function testLogin(): void
    {
        $request = new Request([], [], [], [], [], [
                'REQUEST_URI' => '/incc/login',
            ]);
        $this->controller->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('');
        $authenticationUtils = $this->createStub(AuthenticationUtils::class);
        $result = $this->controller->login($request, $authenticationUtils);
        $this->assertEquals('rendered:inadmin/page/admin/signin.html.twig', $result->getContent());

    }

    public function testLoginRedirect(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login',
        ]);
        $this->controller->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('incc_dashboard');
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/incc/'));
        $authenticationUtils = $this->createStub(AuthenticationUtils::class);
        $result = $this->controller->login($request, $authenticationUtils);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/', $result->getTargetUrl());
    }

    public function testLogout(): void
    {
        $this->expectException(\Exception::class);
        $this->controller->logout();
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPassword(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->once())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->once())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);
        $forgotPasswordAccountLimiter->expects($this->never())
            ->method('create')
            ->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(false);
        $form->expects($this->never())->method('isValid')->willReturn(false);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals('rendered:inadmin/page/admin/forgot-password.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPasswordEmailSent(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->atLeastOnce())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->atLeastOnce())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);
        $forgotPasswordAccountLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);
        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals('rendered:inadmin/page/admin/forgot-password-sent.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPasswordIPRateLimited(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->once())->method('isAccepted')->willReturn(false);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->once())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())->method('create')->willReturn($limiter);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals('Too many attempts from this IP. Try again later.', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPasswordAccountRateLimited(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->atLeastOnce())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->atLeastOnce())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())->method('create')->willReturn($limiter);
        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->atLeastOnce())->method('isAccepted')->willReturn(false);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->atLeastOnce())->method('consume')->willReturn($limit);
        $forgotPasswordAccountLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->never())->method('getForm')->willReturn($form);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);
        $this->controller->expects($this->never())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals('Too many reset attempts for this account. Try again later.', $result->getContent());
    }

    public function testForgotPasswordRedirect(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $forgotPasswordIpLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $this->controller->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('/incc/');
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('/incc/')
            ->willReturn(new RedirectResponse('/incc/'));

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/', $result->getTargetUrl());
    }

    public function testForgotPasswordMailError(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incc/forgot-password',
        ]);
        $request->setSession($session);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);
        $userAccountEmailService->expects($this->once())
            ->method('sendForgotPasswordEmail')
            ->willThrowException(new TransportException('Mailer broken'));

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->atLeastOnce())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->atLeastOnce())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);
        $forgotPasswordAccountLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);
        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals(
            'rendered:inadmin/page/admin/forgot-password-sent.html.twig',
            $result->getContent()
        );
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function testNewPassword(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findOneBy')->willReturn(new User());

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );
        $this->assertEquals('rendered:inadmin/page/admin/new-password.html.twig', $result->getContent());
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function testNewPasswordRedirectIfAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createStub(UserRepository::class);

        $this->controller->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('/incc/');
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('/incc/')
            ->willReturn(new RedirectResponse('/incc/'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/', $result->getTargetUrl());
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function testNewPasswordInvalidToken(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createStub(UserRepository::class);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_account_forgot-password')
            ->willReturn(new RedirectResponse('/incc/forgot-password'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(30)
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/forgot-password', $result->getTargetUrl());
    }

    public function testNewPasswordIPRateLimited(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createStub(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->atLeast(0))->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );
        $this->assertEquals('Too many password reset attempts from this IP. Try again later.', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordMissingUser(): void
    {
        $formData = [
            'change_password' => [
                'username' => 'test',
            ],
        ];
        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(null);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->atLeastOnce())->method('getData')->willReturn($formData);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->once())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->once())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/incc/forgot-password'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/forgot-password', $result->getTargetUrl());
    }

    public function testNewPasswordExpiredToken(): void
    {
        $formData = [
            'change_password' => [
                'username' => 'test',
            ],
        ];
        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createStub(PasswordResetTokenService::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->atLeastOnce())->method('getData')->willReturn($formData);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->once())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->once())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/incc/forgot-password'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/forgot-password', $result->getTargetUrl());
    }

    public function testNewPasswordUpdated(): void
    {
        $formData = [
            'change_password' => [
                'username' => 'test',
                'new_password' => 'new password',
            ],
        ];
        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incc/new-password',
        ]);
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordResetRequest = $this->createStub(PasswordResetRequest::class);
        $tokenService->expects($this->once())
            ->method('validateTokenForUser')
            ->willReturn($passwordResetRequest);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->atLeastOnce())->method('getData')->willReturn($formData);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->expects($this->once())->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->once())->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_account_login')
            ->willReturn(new RedirectResponse('/incc/login'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/login', $result->getTargetUrl());
    }
}
