<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;


use Inachis\Controller\Page\Admin\AccountController;
use Inachis\Entity\User\{PasswordResetRequest, User};
use Inachis\Repository\User\PasswordResetRequestRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Repository\User\UserPasskeyRepository;
use Inachis\Security\Authentication\TotpService;
use Inachis\Security\Authentication\PasskeyService;
use Inachis\Security\Authentication\TwoFactorAuthenticationListener;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
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

    public function setUp(): void
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
            ])
            ->onlyMethods([
                'addFlash', 'createForm', 'createFormBuilder', 'redirectIfAuthenticatedOrNoAdmins',
                'redirectToRoute', 'render', 'generateUrl'
            ])
            ->getMock();
        $this->controller->method('render')
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
        $userRepository->method('findOneBy')->willReturn(new User());

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
            ->with('incc_account_login')
            ->willReturn(new RedirectResponse('/incc/login'));

        $result = $this->controller->newPassword(
            $request, $tokenService, $forgotPasswordIpLimiter, $passwordHasher,
            $userRepository, random_bytes(64)
        );

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/incc/login', $result->getTargetUrl());
    }

    public function testTotpVerifyRedirectsIfNoUserOrNotEnabled(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login/totp',
        ]);
        // Set session
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $totpService = $this->createMock(TotpService::class);

        // Case 1: No user logged in
        $this->security->method('getUser')->willReturn(null);
        $this->controller->expects($this->exactly(2))
            ->method('redirectToRoute')
            ->with('incc_dashboard')
            ->willReturn(new RedirectResponse('/incc/dashboard'));

        $result = $this->controller->totpVerify($request, $totpService);
        $this->assertInstanceOf(RedirectResponse::class, $result);

        // Case 2: User logged in but TOTP not enabled
        $user = new User();
        $user->setTotpEnabled(false);
        $this->security->method('getUser')->willReturn($user);

        $result2 = $this->controller->totpVerify($request, $totpService);
        $this->assertInstanceOf(RedirectResponse::class, $result2);
    }

    public function testTotpVerifyRedirectsIfAlreadyVerified(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login/totp',
        ]);
        $session = new Session(new MockArraySessionStorage());
        $session->set(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY, true);
        $request->setSession($session);

        $user = new User();
        $user->setTotpEnabled(true);
        $this->security->method('getUser')->willReturn($user);

        $totpService = $this->createMock(TotpService::class);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_dashboard')
            ->willReturn(new RedirectResponse('/incc/dashboard'));

        $result = $this->controller->totpVerify($request, $totpService);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testTotpVerifyGetRendersForm(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login/totp',
        ]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $user = new User();
        $user->setTotpEnabled(true);
        $this->security->method('getUser')->willReturn($user);

        $totpService = $this->createMock(TotpService::class);

        $result = $this->controller->totpVerify($request, $totpService);
        $this->assertEquals('rendered:inadmin/page/admin/totp_verify.html.twig', $result->getContent());
    }

    public function testTotpVerifyPostWithInvalidCode(): void
    {
        $request = new Request([], ['totp_code' => '111111'], [], [], [], [
            'REQUEST_URI' => '/incc/login/totp',
        ]);
        $request->setMethod('POST');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $user = new User();
        $user->setTotpEnabled(true);
        $user->setTotpSecret('SECRET');
        $this->security->method('getUser')->willReturn($user);

        $totpService = $this->createMock(TotpService::class);
        $totpService->expects($this->once())
            ->method('verifyCode')
            ->with('SECRET', '111111')
            ->willReturn(false);

        $result = $this->controller->totpVerify($request, $totpService);
        $this->assertEquals('rendered:inadmin/page/admin/totp_verify.html.twig', $result->getContent());
        self::assertFalse($session->has(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY));
    }

    public function testTotpVerifyPostWithValidCode(): void
    {
        $request = new Request([], ['totp_code' => '123456'], [], [], [], [
            'REQUEST_URI' => '/incc/login/totp',
        ]);
        $request->setMethod('POST');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $user = new User();
        $user->setTotpEnabled(true);
        $user->setTotpSecret('SECRET');
        $this->security->method('getUser')->willReturn($user);

        $totpService = $this->createMock(TotpService::class);
        $totpService->expects($this->once())
            ->method('verifyCode')
            ->with('SECRET', '123456')
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_dashboard')
            ->willReturn(new RedirectResponse('/incc/dashboard'));

        $result = $this->controller->totpVerify($request, $totpService);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        self::assertTrue($session->get(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY));
    }

    public function testPasskeyChallenge(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/incc/login/passkey/challenge',
        ]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $passkeyService = $this->createMock(PasskeyService::class);
        $passkeyService->expects($this->once())
            ->method('generateChallenge')
            ->willReturn('CHALLENGE_STRING');
        $passkeyService->expects($this->once())
            ->method('buildRequestOptions')
            ->with(null, 'CHALLENGE_STRING', 'localhost')
            ->willReturn(['challenge' => 'CHALLENGE_STRING']);

        $result = $this->controller->passkeyChallenge($request, $passkeyService);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('CHALLENGE_STRING', $session->get('inachis.passkey.login_challenge'));

        $data = json_decode((string) $result->getContent(), true);
        $this->assertEquals('CHALLENGE_STRING', $data['challenge']);
    }

    public function testPasskeyVerifyMissingChallenge(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login/passkey/verify',
        ]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $passkeyService = $this->createMock(PasskeyService::class);
        $passkeyRepository = $this->createMock(UserPasskeyRepository::class);

        $result = $this->controller->passkeyVerify($request, $passkeyService, $passkeyRepository);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode((string) $result->getContent(), true);
        $this->assertEquals('No active challenge.', $data['error']);
    }

    public function testPasskeyVerifyInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login/passkey/verify',
        ], 'invalid json');
        $session = new Session(new MockArraySessionStorage());
        $session->set('inachis.passkey.login_challenge', 'CHALLENGE');
        $request->setSession($session);

        $passkeyService = $this->createMock(PasskeyService::class);
        $passkeyRepository = $this->createMock(UserPasskeyRepository::class);

        $result = $this->controller->passkeyVerify($request, $passkeyService, $passkeyRepository);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPasskeyVerifyUnknownPasskey(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/incc/login/passkey/verify',
        ], json_encode(['id' => 'UNKNOWN_ID']));
        $session = new Session(new MockArraySessionStorage());
        $session->set('inachis.passkey.login_challenge', 'CHALLENGE');
        $request->setSession($session);

        $passkeyService = $this->createMock(PasskeyService::class);
        $passkeyRepository = $this->createMock(UserPasskeyRepository::class);
        $passkeyRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with('UNKNOWN_ID')
            ->willReturn(null);

        $result = $this->controller->passkeyVerify($request, $passkeyService, $passkeyRepository);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testPasskeyVerifySuccess(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/incc/login/passkey/verify',
        ], json_encode(['id' => 'VALID_ID', 'response' => []]));
        $session = new Session(new MockArraySessionStorage());
        $session->set('inachis.passkey.login_challenge', 'CHALLENGE');
        $request->setSession($session);

        $passkey = $this->createMock(\Inachis\Entity\User\UserPasskey::class);

        $passkeyService = $this->createMock(PasskeyService::class);
        $passkeyRepository = $this->createMock(UserPasskeyRepository::class);
        $passkeyRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with('VALID_ID')
            ->willReturn($passkey);

        $passkeyService->expects($this->once())
            ->method('verifyAssertion')
            ->with($passkey, 'CHALLENGE', 'localhost', $this->callback('is_array'))
            ->willReturn(true);

        $this->controller->expects($this->once())
            ->method('generateUrl')
            ->with('incc_dashboard')
            ->willReturn('/incc/dashboard');

        $result = $this->controller->passkeyVerify($request, $passkeyService, $passkeyRepository);
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode((string) $result->getContent(), true);
        $this->assertEquals('/incc/dashboard', $data['redirect']);
        self::assertFalse($session->has('inachis.passkey.login_challenge'));
        self::assertTrue($session->get(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY));
    }
}

