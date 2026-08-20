<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\User;

use Inachis\Entity\User\User;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class UserAccountEmailServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private PasswordResetTokenService $tokenService;
    private PageViewFactory $pageViewFactory;

    /** @var array<string, mixed> */
    private array $settings;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->tokenService = $this->createMock(PasswordResetTokenService::class);

        $pageView = new PageView(
            new SiteSettings(
                siteTitle: '',
                domain: '',
                google: [],
                language: 'en',
                textDirection: 'ltr',
                abstract: '',
                geotagContent: false,
                displayTimezone: 'UTC',
            ),
            new PageMetadata(),
        );

        $this->pageViewFactory = $this->createMock(PageViewFactory::class);
        $this->pageViewFactory
            ->method('create')
            ->willReturn($pageView);

        $this->settings = [
            'clientIP' => '127.0.0.1',
            'siteTitle' => 'ExampleSite',
        ];
    }

    public function testRegisterNewUser(): void
    {
        $user = (new User())
            ->setEmail('john@example.com')
            ->setDisplayName('John Doe');

        $tokenData = [
            'token' => 'XYZ123',
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00'),
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00'),
        ];

        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->with('john@example.com')
            ->willReturn($tokenData);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email): bool {
                self::assertSame('john@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Welcome to ExampleSite', $email->getSubject());
                self::assertSame('inadmin/emails/registration.html.twig', $email->getHtmlTemplate());
                self::assertSame('inadmin/emails/registration.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();

                self::assertInstanceOf(PageView::class, $context['viewModel']);
                self::assertSame(
                    'Wednesday 1st January 2025 at 15:00',
                    $context['expiresAt'],
                );
                self::assertSame('John Doe', $context['name']);
                self::assertSame($this->settings, $context['settings']);
                self::assertSame('https://site/reset/XYZ123', $context['url']);
                self::assertArrayHasKey('logo', $context);
                self::assertIsString($context['logo']);

                return true;
            }));

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->registerNewUser(
            $user,
            $this->settings,
            static fn (string $token): string => "https://site/reset/$token",
        );
    }

    public function testRegisterNewUserWithNoEmailDoesNothing(): void
    {
        $user = new User();

        $this->tokenService
            ->expects($this->never())
            ->method('createResetRequestForEmail');

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->registerNewUser(
            $user,
            [],
            static fn (): string => '',
        );
    }

    public function testRegisterNewUserWithInvalidTokenDoesNothing(): void
    {
        $user = (new User())
            ->setEmail('john@example.com');

        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->willReturn([]);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->registerNewUser(
            $user,
            [],
            static fn (): string => '',
        );
    }

    public function testSendForgotPasswordEmail(): void
    {
        $user = (new User())
            ->setEmail('john@example.com')
            ->setDisplayName('John Doe');

        $tokenData = [
            'token' => 'XYZ123',
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00'),
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00'),
        ];

        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->with('john@example.com')
            ->willReturn($tokenData);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email): bool {
                self::assertSame('john@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Reset your password for ExampleSite', $email->getSubject());
                self::assertSame('inadmin/emails/forgot-password.html.twig', $email->getHtmlTemplate());
                self::assertSame('inadmin/emails/forgot-password.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();

                self::assertInstanceOf(PageView::class, $context['viewModel']);
                self::assertSame(
                    'Wednesday 1st January 2025 at 15:00',
                    $context['expiresAt'],
                );
                self::assertSame('127.0.0.1', $context['ipAddress']);
                self::assertSame('/incp/new-password/XYZ123', $context['url']);
                self::assertArrayHasKey('logo', $context);
                self::assertIsString($context['logo']);

                return true;
            }));

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->sendForgotPasswordEmail(
            $user,
            $this->settings,
            static fn (string $token): string => "/incp/new-password/$token",
        );
    }

    public function testSendForgotPasswordEmailWithNoEmailDoesNothing(): void
    {
        $user = new User();

        $this->tokenService
            ->expects($this->never())
            ->method('createResetRequestForEmail');

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->sendForgotPasswordEmail(
            $user,
            [],
            static fn (): string => '',
        );
    }

    public function testSendForgotPasswordEmailWithInvalidTokenDoesNothing(): void
    {
        $user = (new User())
            ->setEmail('john@example.com');

        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->willReturn([]);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->pageViewFactory,
        );

        $service->sendForgotPasswordEmail(
            $user,
            [],
            static fn (): string => '',
        );
    }
}
