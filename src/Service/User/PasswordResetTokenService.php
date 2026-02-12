<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\User;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\{PasswordResetRequest,User};
use Inachis\Repository\UserRepository;
use Inachis\Repository\PasswordResetRequestRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Random\RandomException;

/**
 * Service for managing password reset tokens
 */
class PasswordResetTokenService
{
    /**
     * @var string
     */
    private string $appSecret;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var PasswordResetRequestRepository
     */
    private PasswordResetRequestRepository $passwordResetRequestRepository;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var int The lifetime for the password reset token. Default is 1800 seconds (30 minutes)
     */
    private int $ttlSeconds;

    /**
     * Construct the password reset token service
     * 
     * @param string $appSecret
     * @param EntityManagerInterface $entityManager
     * @param PasswordResetRequestRepository $passwordResetRequestRepository
     * @param UserRepository $userRepository
     * @param int $ttlSeconds
     */
    public function __construct(
        string $appSecret,
        EntityManagerInterface $entityManager,
        PasswordResetRequestRepository $passwordResetRequestRepository,
        UserRepository $userRepository,
        int $ttlSeconds = 1800
    ) {
        $this->appSecret = $appSecret;
        $this->entityManager = $entityManager;
        $this->passwordResetRequestRepository = $passwordResetRequestRepository;
        $this->userRepository = $userRepository;
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * Create a password reset request for an email address
     * 
     * @param string $email
     * @return array<string,mixed>|null
     * @throws RandomException
     * @throws Exception
     */
    public function createResetRequestForEmail(string $email): ?array
    {
        /** @var \Inachis\Entity\User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return null;
        }
        /** @var \Inachis\Entity\PasswordResetRequest[] $existingRequests */
        $existingRequests = $this->passwordResetRequestRepository->findActiveByUser($user);
        foreach ($existingRequests as $request) {
            $request->markUsed();
            $this->entityManager->persist($request);
        }

        $raw = bin2hex(random_bytes(32));
        $hash = hash_hmac('sha256', $raw, $this->appSecret);
        $expires = new DateTimeImmutable(sprintf('+%d seconds', $this->ttlSeconds));

        $passwordResetRequest = new PasswordResetRequest($user, $hash, $expires);
        $this->entityManager->persist($passwordResetRequest);
        $this->entityManager->flush();

        return [
            'token' => $raw,
            'expiresAt' => $expires,
            'user' => $user,
        ];
    }

    /**
     * Validate a password reset token for a user
     * 
     * @param string $rawToken
     * @param User|null $user
     * @return PasswordResetRequest|null
     * @throws NonUniqueResultException
     */
    public function validateTokenForUser(string $rawToken, ?User $user = null): ?PasswordResetRequest
    {
        $hash = hash_hmac('sha256', $rawToken, $this->appSecret);

        if ($user !== null) {
            $candidate = $this->passwordResetRequestRepository->findLatestActiveForUser($user);
        } else {
            $candidate = $this->passwordResetRequestRepository->findLatestActiveByHash($hash);
        }
        if (!$candidate) {
            return null;
        }
        $now = new DateTimeImmutable();
        if ($candidate->getExpiresAt() < $now) {
            return null;
        }
        if (!hash_equals($candidate->getTokenHash(), $hash)) {
            return null;
        }

        return $candidate;
    }

    /**
     * Mark a password reset request as used
     * 
     * @param PasswordResetRequest $request
     * @return void
     */
    public function markAsUsed(PasswordResetRequest $request): void
    {
        $request->markUsed();
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }
}
