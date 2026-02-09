<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventListener;

use Inachis\Entity\{LoginActivity, User};
use Inachis\Repository\LoginActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * LoginSuccessListener for logging successful login attempts.
 */
class LoginSuccessListener
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     * @param LoginActivityRepository $loginActivityRepository
     * @param MailerInterface $mailer
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
        protected LoginActivityRepository $loginActivityRepository,
        protected MailerInterface $mailer,
    ) {}

    /**
     * Logs a successful login attempt.
     *
     * @param LoginSuccessEvent $event
     */
    public function __invoke(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $user = $event->getUser();
        $firewallName = $event->getFirewallName();
        $ip = $request?->getClientIp();
        $userAgent = $request?->headers->get('User-Agent');
        $sessionId = $request?->getSession()?->getId();
        $fingerprint = hash('sha512', $ip . '|' . $userAgent);
        $activity = new LoginActivity(
            $user,
            'success',
            $ip,
            $userAgent,
            $sessionId,
            null,
            [
                'fingerprint' => $fingerprint,
                'roles' => $user->getRoles(),
            ]
        );

        $isKnownDevice = $this->loginActivityRepository->deviceExists(
            $user,
            $fingerprint
        );

        if (!$isKnownDevice) {
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
                    ])
            );
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }  
}
