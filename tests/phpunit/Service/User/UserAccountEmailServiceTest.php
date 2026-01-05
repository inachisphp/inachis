<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\User;

use Inachis\Entity\User;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class UserAccountEmailServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private PasswordResetTokenService $tokenService;
    private EntityManagerInterface $entityManager;
    private array $settings;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->tokenService = $this->createMock(PasswordResetTokenService::class);

        $this->settings = [
            'clientIP' => '127.0.0.1',
            'siteTitle' => 'ExampleSite',
        ];
    }

    public function testRegisterNewUser(): void
    {
        $user = new User();
        $user->setEmail('john@example.com');
        $user->setDisplayName('John Doe');

        $fakeTokenData = [
            'token' => 'XYZ123',
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00')
        ];

        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->with('john@example.com')
            ->willReturn($fakeTokenData);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($fakeTokenData) {

                $this->assertEquals('john@example.com', $email->getTo()[0]->getAddress());
                $this->assertEquals('Welcome to ExampleSite', $email->getSubject());
                $this->assertEquals('inadmin/emails/registration.html.twig', $email->getHtmlTemplate());
                $this->assertEquals('inadmin/emails/registration.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();
                $this->assertEquals('John Doe', $context['name']);
                $this->assertEquals('https://site/reset/' . $fakeTokenData['token'], $context['url']);
                $this->assertStringContainsString('data:image/png;base64,', $context['logo']);
                $this->assertEquals('ExampleSite', $context['settings']['siteTitle']);

                return true;
            }));

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->entityManager,
        );

        $urlGenerator = fn(string $token) => "https://site/reset/$token";

        $service->registerNewUser($user, $this->settings, $urlGenerator);
    }

    public function testSendForgotPasswordEmail(): void
    {
        $user = new User();
        $user->setEmail('john@example.com')->setDisplayName('John Doe');

        $fakeTokenData = [
            'token' => 'XYZ123',
            'expiresAt' => new \DateTimeImmutable('2025-01-01 15:00')
        ];

        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->tokenService
            ->expects($this->once())
            ->method('createResetRequestForEmail')
            ->with('john@example.com')
            ->willReturn($fakeTokenData);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($fakeTokenData) {

                $this->assertEquals('john@example.com', $email->getTo()[0]->getAddress());
                $this->assertEquals('Reset your password for ExampleSite', $email->getSubject());
                $this->assertEquals('inadmin/emails/forgot-password.html.twig', $email->getHtmlTemplate());
                $this->assertEquals('inadmin/emails/forgot-password.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();
                $this->assertEquals('/incc/new-password/' . $fakeTokenData['token'], $context['url']);
                $this->assertStringContainsString('data:image/png;base64,', $context['logo']);
                $this->assertEquals('ExampleSite', $context['settings']['siteTitle']);

                return true;
            }));

        $service = new UserAccountEmailService(
            $this->mailer,
            $this->tokenService,
            $this->entityManager,
        );

        $urlGenerator = fn(string $token) => "/incc/new-password/$token";

        $service->sendForgotPasswordEmail($user, $this->settings, $urlGenerator);
    }
}