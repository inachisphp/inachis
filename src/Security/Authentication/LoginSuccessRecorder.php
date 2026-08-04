<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\LoginActivity;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\LoginResultType;
use Inachis\Repository\User\LoginActivityRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Records a completed interactive login.
 */
class LoginSuccessRecorder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginActivityRepository $loginActivityRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Record a successful login.
     */
    public function record(
        User $user,
        Request $request,
        LoginResultType $resultType = LoginResultType::TYPE_SUCCESS,
    ): void {
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');
        $sessionId = $request->getSession()->getId();
        $fingerprint = hash('sha512', $ip.'|'.$userAgent);

        $activity = new LoginActivity(
            $user,
            $resultType,
            $ip,
            $userAgent,
            $sessionId,
            null,
            [
                'fingerprint' => $fingerprint,
                'roles' => $user->getRoles(),
            ],
        );

        $isKnownDevice = $this->loginActivityRepository->deviceExists(
            $user,
            $fingerprint,
        );

        if (!$isKnownDevice && !empty($user->getEmail())) {
            $this->mailer->send(
                (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('New device sign-in detected')
                    ->htmlTemplate('emails/new_device.html.twig')
                    ->textTemplate('emails/new_device.txt.twig')
                    ->context([
                        'ip' => $ip,
                        'userAgent' => $userAgent,
                        'time' => new \DateTimeImmutable(),
                    ]),
            );
        }

        $user->setLastLoginAt(new \DateTimeImmutable());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}
