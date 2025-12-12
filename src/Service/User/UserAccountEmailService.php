<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\User;

use App\Entity\User;
use App\Util\Base64EncodeFile;
use App\Util\RandomColorPicker;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserAccountEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private PasswordResetTokenService $tokenService,
        private EntityManagerInterface $entityManager,
    ) {}

    public function sendForgotPasswordEmail(User $user, array $settings, callable $urlGenerator): void
    {
        $data = $this->tokenService->createResetRequestForEmail($user->getEmail());
//        $this->entityManager->persist($user);
//        $this->entityManager->flush();

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject('Reset your password for ' . $settings['siteTitle'])
            ->htmlTemplate('inadmin/emails/forgot-password.html.twig')
            ->textTemplate('inadmin/emails/forgot-password.txt.twig')
            ->context([
                'ipAddress' => $settings['clientIP'],
                'url' => $urlGenerator('incc_account_new-password', [ 'token' => $data['token']]),
                'expiresAt' => $data['expiresAt']->format('l jS F Y \a\\t H:i'),
                'settings' => $settings,
                'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
            ]);
        $this->mailer->send($email);
    }

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function registerNewUser(User $user, array $settings, callable $urlGenerator): void
    {
        $data = $this->tokenService->createResetRequestForEmail($user->getEmail());

        $user->setColor(RandomColorPicker::generate());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject('Welcome to ' . $settings['siteTitle'])
            ->htmlTemplate('inadmin/emails/registration.html.twig')
            ->textTemplate('inadmin/emails/registration.txt.twig')
            ->context([
                'name' => $user->getDisplayName(),
                'url' => $urlGenerator($data['token']),
                'expiresAt' => $data['expiresAt']->format('l jS F Y \a\\t H:i'),
                'settings' => $settings,
                'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
            ])
        ;
        $this->mailer->send($email);
    }
}
