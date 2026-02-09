<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventListener;

use Inachis\Entity\LoginActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * LoginFailureListener for logging failed login attempts.
 */
class LoginFailureListener
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Logs a failed login attempt.
     * 
     * @param LoginFailureEvent $event
     */
    public function __invoke(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $ip = $request?->getClientIp();
        $userAgent = $request?->headers->get('User-Agent');
        $submittedUsername = $request?->request->all('login')['loginUsername'] ?? null;
        $exception = $event->getException();

        // if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
        //     // Rate limit exceeded
        // } else {
        //     // Bad credentials / user not found / disabled account
        // }

        $activity = new LoginActivity(
            null,
            'failure',
            $ip,
            $userAgent,
            null,
            $submittedUsername,
            [
                'error' => $exception->getMessageKey(),
            ]
        );

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}