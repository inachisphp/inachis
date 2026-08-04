<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Inachis\Entity\User\PasswordResetRequest;
use Inachis\Entity\User\User;
use Inachis\Repository\User\PasswordResetRequestRepository;
use Inachis\Repository\User\UserRepository;
use Random\RandomException;

/**
 * Service for managing password reset tokens.
 */
class PasswordResetTokenService
{
    /** @var string The application secret used for hashing the tokens */
    private string $appSecret;

    private EntityManagerInterface $entityManager;

    private PasswordResetRequestRepository $passwordResetRequestRepository;

    private UserRepository $userRepository;

    /** @var int The lifetime for the password reset token. Default is 1800 seconds (30 minutes) */
    private int $ttlSeconds;

    /**
     * Construct the password reset token service.
     */
    public function __construct(
        string $appSecret,
        EntityManagerInterface $entityManager,
        PasswordResetRequestRepository $passwordResetRequestRepository,
        UserRepository $userRepository,
        int $ttlSeconds = 1800,
    ) {
        $this->appSecret = $appSecret;
        $this->entityManager = $entityManager;
        $this->passwordResetRequestRepository = $passwordResetRequestRepository;
        $this->userRepository = $userRepository;
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * Create a password reset request for an email address.
     *
     * @return array<string,mixed>|null
     *
     * @throws RandomException
     * @throws \Exception
     */
    public function createResetRequestForEmail(string $email): ?array
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return null;
        }
        /** @var PasswordResetRequest[] $existingRequests */
        $existingRequests = $this->passwordResetRequestRepository->findActiveByUser($user);
        foreach ($existingRequests as $request) {
            $request->markUsed();
            $this->entityManager->persist($request);
        }
        $raw = bin2hex(random_bytes(32));
        $hash = hash_hmac('sha256', $raw, $this->appSecret);
        $expires = new \DateTimeImmutable(sprintf('+%d seconds', $this->ttlSeconds));
        $passwordResetRequest = new PasswordResetRequest($user, $hash, $expires);
        $this->entityManager->persist($passwordResetRequest);

        return [
            'token' => $raw,
            'expiresAt' => $expires,
            'user' => $user,
        ];
    }

    /**
     * Validate a password reset token for a user.
     *
     * @throws NonUniqueResultException
     */
    public function validateTokenForUser(string $rawToken, ?User $user = null): ?PasswordResetRequest
    {
        $hash = hash_hmac('sha256', $rawToken, $this->appSecret);

        if (null !== $user) {
            $candidate = $this->passwordResetRequestRepository->findLatestActiveForUser($user);
        } else {
            $candidate = $this->passwordResetRequestRepository->findLatestActiveByHash($hash);
        }
        if (!$candidate) {
            return null;
        }
        $now = new \DateTimeImmutable();
        if ($candidate->getExpiresAt() < $now) {
            return null;
        }
        if (!hash_equals($candidate->getTokenHash(), $hash)) {
            return null;
        }

        return $candidate;
    }

    /**
     * Mark a password reset request as used.
     */
    public function markAsUsed(PasswordResetRequest $request): void
    {
        $request->markUsed();
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }
}
