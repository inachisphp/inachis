<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\AccountController;
use Inachis\Entity\User\PasswordResetRequest;
use Inachis\Entity\User\User;
use Inachis\Repository\User\PasswordResetRequestRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Random\RandomException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AccountControllerTest extends InachisControllerTestCase
{
    /** @var AccountController&MockObject */
    protected AccountController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->getMockBuilder(AccountController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'createFormBuilder',
                'redirectIfAuthenticatedOrNoAdmins',
                'redirectToRoute',
                'render',
                'generateUrl',
            ])
            ->getMock();

        $this->controller
            ->method('render')
            ->willReturnCallback(
                static function (string $template, array $data): Response {
                    return new Response('rendered:'.$template);
                },
            );
    }

    public function testLogin(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/login',
        ]);

        $this->controller
            ->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('');

        $authenticationUtils = $this->createStub(AuthenticationUtils::class);

        $result = $this->controller->login($request, $authenticationUtils);

        $this->assertEquals(
            'rendered:inadmin/page/admin/signin.html.twig',
            $result->getContent(),
        );
    }

    public function testLoginRedirect(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/login',
        ]);

        $this->controller
            ->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('incc_dashboard');

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_dashboard')
            ->willReturn(new RedirectResponse('/incp/'));

        $authenticationUtils = $this->createStub(AuthenticationUtils::class);

        $result = $this->controller->login($request, $authenticationUtils);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incp/', $result->getTargetUrl());
    }

    public function testLogout(): void
    {
        $this->expectException(\LogicException::class);

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
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->never())
            ->method('findOneBy');

        $userAccountEmailService = $this->createStub(
            UserAccountEmailService::class,
        );

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $forgotPasswordAccountLimiter
            ->expects($this->never())
            ->method('create');

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);

        $form
            ->expects($this->never())
            ->method('isValid');

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertEquals(
            'rendered:inadmin/page/admin/forgot-password.html.twig',
            $result->getContent(),
        );
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
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'email' => 'test@example.com',
            ])
            ->willReturn(new User());

        $userAccountEmailService = $this->createStub(
            UserAccountEmailService::class,
        );

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->atLeastOnce())
            ->method('isAccepted')
            ->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->atLeastOnce())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $forgotPasswordAccountLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formBuilder = $this->createMock(FormBuilder::class);

        $formBuilder
            ->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller
            ->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertEquals(
            'rendered:inadmin/page/admin/forgot-password-sent.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPasswordIpRateLimited(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->never())
            ->method('findOneBy');

        $userAccountEmailService = $this->createStub(
            UserAccountEmailService::class,
        );

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(false);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertEquals(
            'Too many requests. Try again later.',
            $result->getContent(),
        );
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
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->never())
            ->method('findOneBy');

        $userAccountEmailService = $this->createStub(
            UserAccountEmailService::class,
        );

        $ipLimit = $this->createMock(RateLimit::class);

        $ipLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $ipLimiter = $this->createMock(LimiterInterface::class);

        $ipLimiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($ipLimit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($ipLimiter);

        $accountLimit = $this->createMock(RateLimit::class);

        $accountLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(false);

        $accountLimiter = $this->createMock(LimiterInterface::class);

        $accountLimiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($accountLimit);

        $forgotPasswordAccountLimiter
            ->expects($this->once())
            ->method('create')
            ->with('test@example.com')
            ->willReturn($accountLimiter);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertEquals(
            'Too many reset attempts. Try again later.',
            $result->getContent(),
        );
    }

    public function testForgotPasswordRedirect(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->never())
            ->method('findOneBy');

        $userAccountEmailService = $this->createStub(
            UserAccountEmailService::class,
        );

        $this->controller
            ->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('/incc/');

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('/incc/')
            ->willReturn(new RedirectResponse('/incc/'));

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testForgotPasswordMailError(): void
    {
        $request = new Request([], [
            'forgot_password' => [
                'forgot_email' => 'test@example.com',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incp/forgot-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $forgotPasswordAccountLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $passwordResetRequestRepository = $this->createStub(
            PasswordResetRequestRepository::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'email' => 'test@example.com',
            ])
            ->willReturn(new User());

        $userAccountEmailService = $this->createMock(
            UserAccountEmailService::class,
        );

        $userAccountEmailService
            ->expects($this->once())
            ->method('sendForgotPasswordEmail')
            ->willThrowException(
                new TransportException('Mailer broken'),
            );

        $ipLimit = $this->createMock(RateLimit::class);

        $ipLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $ipLimiter = $this->createMock(LimiterInterface::class);

        $ipLimiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($ipLimit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($ipLimiter);

        $accountLimit = $this->createMock(RateLimit::class);

        $accountLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $accountLimiter = $this->createMock(LimiterInterface::class);

        $accountLimiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($accountLimit);

        $forgotPasswordAccountLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($accountLimiter);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formBuilder = $this->createMock(FormBuilder::class);

        $formBuilder
            ->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller
            ->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request,
            $passwordResetRequestRepository,
            $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter,
            $userAccountEmailService,
            $userRepository,
        );

        $this->assertEquals(
            'rendered:inadmin/page/admin/forgot-password-sent.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPassword(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createStub(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->never())
            ->method('findOneBy');

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertEquals(
            'rendered:inadmin/page/admin/new-password.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordRedirectIfAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createStub(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createStub(
            UserRepository::class,
        );

        $this->controller
            ->expects($this->once())
            ->method('redirectIfAuthenticatedOrNoAdmins')
            ->willReturn('/incc/');

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('/incc/')
            ->willReturn(new RedirectResponse('/incc/'));

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/', $result->getTargetUrl());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordInvalidToken(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createStub(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createStub(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createStub(
            UserRepository::class,
        );

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_account_forgot-password')
            ->willReturn(
                new RedirectResponse('/incp/forgot-password'),
            );

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(30),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            '/incp/forgot-password',
            $result->getTargetUrl(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordIpRateLimited(): void
    {
        $request = new Request([], [
            'change_password' => [
                'username' => 'test',
                'new_password' => 'new password',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createStub(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createStub(
            UserRepository::class,
        );

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(false);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertEquals(
            'Too many reset attempts. Try again later.',
            $result->getContent(),
        );
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
                'new_password' => 'new password',
            ],
        ];

        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createStub(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'username' => 'test',
            ])
            ->willReturn(null);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($formData);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_account_forgot-password')
            ->willReturn(
                new RedirectResponse('/incp/forgot-password'),
            );

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            '/incp/forgot-password',
            $result->getTargetUrl(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordExpiredToken(): void
    {
        $formData = [
            'change_password' => [
                'username' => 'test',
                'new_password' => 'new password',
            ],
        ];

        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createMock(
            PasswordResetTokenService::class,
        );

        $passwordHasher = $this->createStub(
            UserPasswordHasherInterface::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $user = new User();

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'username' => 'test',
            ])
            ->willReturn($user);

        $tokenService
            ->expects($this->once())
            ->method('validateTokenForUser')
            ->with(
                $this->isString(),
                $user,
            )
            ->willReturn(null);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($formData);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_account_forgot-password')
            ->willReturn(
                new RedirectResponse('/incp/forgot-password'),
            );

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            '/incp/forgot-password',
            $result->getTargetUrl(),
        );
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testNewPasswordUpdated(): void
    {
        $formData = [
            'change_password' => [
                'username' => 'test',
                'new_password' => 'new password',
            ],
        ];

        $request = new Request([], $formData, [], [], [], [
            'REQUEST_URI' => '/incp/new-password',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $forgotPasswordIpLimiter = $this->createMock(
            RateLimiterFactoryInterface::class,
        );

        $tokenService = $this->createMock(
            PasswordResetTokenService::class,
        );

        $passwordResetRequest = $this->createStub(
            PasswordResetRequest::class,
        );

        $tokenService
            ->expects($this->once())
            ->method('validateTokenForUser')
            ->willReturn($passwordResetRequest);

        $tokenService
            ->expects($this->once())
            ->method('markAsUsed')
            ->with($passwordResetRequest);

        $passwordHasher = $this->createMock(
            UserPasswordHasherInterface::class,
        );

        $user = new User();

        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'new password')
            ->willReturn('hashed-password');

        $userRepository = $this->createMock(UserRepository::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'username' => 'test',
            ])
            ->willReturn($user);

        $form = $this->createMock(Form::class);

        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($formData);

        $this->controller
            ->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $limit = $this->createMock(RateLimit::class);

        $limit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);

        $limiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($limit);

        $forgotPasswordIpLimiter
            ->expects($this->once())
            ->method('create')
            ->willReturn($limiter);

        $this->controller
            ->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_account_login')
            ->willReturn(
                new RedirectResponse('/incp/login'),
            );

        $result = $this->controller->newPassword(
            $request,
            $tokenService,
            $forgotPasswordIpLimiter,
            $passwordHasher,
            $userRepository,
            random_bytes(64),
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            '/incp/login',
            $result->getTargetUrl(),
        );

        $this->assertEquals(
            'hashed-password',
            $user->getPassword(),
        );

        $this->assertNotNull($user->getPasswordChangedAt());
    }
}
