<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\LoginActivity;
use Inachis\Enum\Security\LoginResultType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * LoginFailureListener for logging failed login attempts.
 */
class LoginFailureListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
    ) {
    }

    /**
     * Logs a failed login attempt.
     */
    public function __invoke(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');
        /** @var string $submittedUsername */
        $submittedUsername = $request->request->all('login')['loginUsername'] ?? '';
        $exception = $event->getException();

        // if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
        //     // Rate limit exceeded
        // } else {
        //     // Bad credentials / user not found / disabled account
        // }

        $activity = new LoginActivity(
            null,
            LoginResultType::TYPE_FAILURE,
            $ip,
            $userAgent,
            null,
            $submittedUsername,
            [
                'error' => $exception->getMessageKey(),
            ],
        );

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}
