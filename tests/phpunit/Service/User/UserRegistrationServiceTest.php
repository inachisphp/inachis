<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\User;

use App\Entity\User;
use App\Service\User\PasswordResetTokenService;
use App\Service\User\UserRegistrationService;
use App\Util\Base64EncodeFile;
use App\Util\RandomColorPicker;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class UserRegistrationServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private PasswordResetTokenService $tokenService;
    private EntityManagerInterface $entityManager;
    private array $settings;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->tokenService = $this->createMock(PasswordResetTokenService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->settings = [
            'siteTitle' => 'ExampleSite'
        ];

        // Mock static methods
//        $this->mockStatic(RandomColorPicker::class)
//            ->method('generate')
//            ->willReturn('#abcdef');
//
//        $this->mockStatic(Base64EncodeFile::class)
//            ->method('encode')
//            ->willReturn('base64-image-data');
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

        $this->tokenService->method('createResetRequestForEmail')
            ->with('john@example.com')
            ->willReturn($fakeTokenData);

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

        $service = new UserRegistrationService(
            $this->mailer,
            $this->tokenService,
            $this->entityManager,
            $this->settings
        );

        $urlGenerator = fn(string $token) => "https://site/reset/$token";
        $settings = [
            'siteTitle' => 'ExampleSite',
        ];

        $service->registerNewUser($user, $settings, $urlGenerator);
    }
}