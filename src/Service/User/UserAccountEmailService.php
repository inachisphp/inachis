<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\User;

use Inachis\Entity\User;
use Inachis\Util\Base64EncodeFile;
use Inachis\Util\RandomColorPicker;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for sending user account emails
 */
readonly class UserAccountEmailService
{
    /**
     * @param MailerInterface $mailer
     * @param PasswordResetTokenService $tokenService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private MailerInterface $mailer,
        private PasswordResetTokenService $tokenService,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Send a forgot password email to a user
     * 
     * @param User $user
     * @param array<string, mixed> $data
     * @param callable $urlGenerator
     * @return void
     */
    public function sendForgotPasswordEmail(User $user, array $data, callable $urlGenerator): void
    {
        $emailAddress = $user->getEmail();
        if (empty($emailAddress)) {
            return;
        }
        $tokenData = $this->tokenService->createResetRequestForEmail($emailAddress);
        if (empty($tokenData) || empty($tokenData['token']) 
            || empty($tokenData['expiresAt']) || !($tokenData['expiresAt'] instanceof \DateTimeImmutable)) {
            return;
        }

        // @todo record mod timestamp
//        $this->entityManager->persist($user);
//        $this->entityManager->flush();

        /** @var string $siteTitle */
        $siteTitle = $data['siteTitle'] ?? 'Inachis Admin Panel';
        $email = (new TemplatedEmail())
            ->to(new Address($emailAddress))
            ->subject('Reset your password for ' . $siteTitle)
            ->htmlTemplate('inadmin/emails/forgot-password.html.twig')
            ->textTemplate('inadmin/emails/forgot-password.txt.twig')
            ->context([
                'ipAddress' => $data['clientIP'] ?? '',
                'url' => $urlGenerator($tokenData['token']) ?? '',
                'expiresAt' => $tokenData['expiresAt']->format('l jS F Y \a\\t H:i'),
                'settings' => $data['settings'] ?? [],
                'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
            ]);
        $this->mailer->send($email);
    }

    /**
     * Register a new user and send them an email
     * 
     * @param User $user
     * @param array<string, mixed> $settings
     * @param callable $urlGenerator
     * @return void
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function registerNewUser(User $user, array $settings, callable $urlGenerator): void
    {
        $emailAddress = $user->getEmail();
        if (empty($emailAddress)) {
            return;
        }
        $tokenData = $this->tokenService->createResetRequestForEmail($emailAddress);
        if (empty($tokenData) || empty($tokenData['token']) 
            || empty($tokenData['expiresAt']) || !($tokenData['expiresAt'] instanceof \DateTimeImmutable)) {
            return;
        }

        // @todo move this block to the controller
        $user->getPreferences()->setColor(RandomColorPicker::generate());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /** @var string $siteTitle */
        $siteTitle = $settings['siteTitle'] ?? 'Inachis Admin Panel';
        $email = (new TemplatedEmail())
            ->to(new Address($emailAddress))
            ->subject('Welcome to ' . $siteTitle)
            ->htmlTemplate('inadmin/emails/registration.html.twig')
            ->textTemplate('inadmin/emails/registration.txt.twig')
            ->context([
                'name' => $user->getDisplayName(),
                'url' => $urlGenerator($tokenData['token']),
                'expiresAt' => $tokenData['expiresAt']->format('l jS F Y \a\\t H:i'),
                'settings' => $settings,
                'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
            ])
        ;
        $this->mailer->send($email);
    }
}
