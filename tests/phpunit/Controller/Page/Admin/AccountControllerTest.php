<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Admin;


use App\Controller\Page\Admin\AccountController;
use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use App\Service\User\PasswordResetTokenService;
use App\Service\User\UserAccountEmailService;
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(Translator::class);
        $this->controller = $this->getMockBuilder(AccountController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods([
                'addFlash', 'createForm', 'createFormBuilder', 'redirectIfAuthenticatedOrNoAdmins',
                'redirectToRoute', 'render'
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
        $this->controller->method('redirectIfAuthenticatedOrNoAdmins')->willReturn('');
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $result = $this->controller->login($request, $authenticationUtils);
        $this->assertEquals('rendered:inadmin/page/admin/signin.html.twig', $result->getContent());

    }

    public function testLoginRedirect(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/login',
        ]);
        $this->controller->method('redirectIfAuthenticatedOrNoAdmins')->willReturn('incc_dashboard');
        $this->controller->method('redirectToRoute')->willReturn(new RedirectResponse('/incc/'));
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
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
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);
        $forgotPasswordAccountLimiter->method('create')->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(false);
        $this->controller->method('createForm')->willReturn($form);

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
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);
        $forgotPasswordAccountLimiter->method('create')->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')->willReturn($form);
        $this->controller->method('createForm')->willReturn($form);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

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
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(false);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);

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
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);
        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(false);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordAccountLimiter->method('create')->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')->willReturn($form);
        $this->controller->method('createForm')->willReturn($form);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

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
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $forgotPasswordAccountLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $this->controller->method('redirectIfAuthenticatedOrNoAdmins')->willReturn('/incc/');
        $this->controller
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
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);
        $userAccountEmailService
            ->method('sendForgotPasswordEmail')
            ->willThrowException(new TransportException('Mailer broken'));

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);
        $forgotPasswordAccountLimiter->method('create')->willReturn($limiter);

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')->willReturn($form);
        $this->controller->method('createForm')->willReturn($form);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

        $result = $this->controller->forgotPassword(
            $request, $passwordResetRequestRepository, $forgotPasswordIpLimiter,
            $forgotPasswordAccountLimiter, $userAccountEmailService, $userRepository
        );
        $this->assertEquals('rendered:inadmin/page/admin/forgot-password-sent.html.twig', $result->getContent());
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
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

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
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);

        $this->controller->method('redirectIfAuthenticatedOrNoAdmins')->willReturn('/incc/');
        $this->controller
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
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);

        $this->controller
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
        $forgotPasswordIpLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $this->controller->method('createForm')->willReturn($form);

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
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn($formData);
        $this->controller->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);

        $this->controller->method('redirectToRoute')->willReturn(new RedirectResponse('/incc/forgot-password'));

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
        $tokenService = $this->createMock(PasswordResetTokenService::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn($formData);
        $this->controller->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);

        $this->controller->method('redirectToRoute')->willReturn(new RedirectResponse('/incc/forgot-password'));

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
        $passwordResetRequest = $this->createMock(PasswordResetRequest::class);
        $tokenService->method('validateTokenForUser')->willReturn($passwordResetRequest);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn($formData);
        $this->controller->method('createForm')->willReturn($form);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->willReturn($limit);
        $forgotPasswordIpLimiter->method('create')->willReturn($limiter);

        $this->controller
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
