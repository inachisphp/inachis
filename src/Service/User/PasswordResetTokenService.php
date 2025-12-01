<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\User;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PasswordResetRequest;
use App\Repository\UserRepository;
use App\Repository\PasswordResetRequestRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Random\RandomException;

/**
 *
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
    private EntityManagerInterface $em;
    /**
     * @var PasswordResetRequestRepository
     */
    private PasswordResetRequestRepository $requestRepo;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepo;
    /**
     * @var int The lifetime for the password reset token. Default is 1800 seconds (30 minutes)
     */
    private int $ttlSeconds;

    /**
     * @param string $appSecret
     * @param EntityManagerInterface $em
     * @param PasswordResetRequestRepository $requestRepo
     * @param UserRepository $userRepo
     * @param int $ttlSeconds
     */
    public function __construct(
        string $appSecret,
        EntityManagerInterface $em,
        PasswordResetRequestRepository $requestRepo,
        UserRepository $userRepo,
        int $ttlSeconds = 1800
    ) {
        $this->appSecret = $appSecret;
        $this->em = $em;
        $this->requestRepo = $requestRepo;
        $this->userRepo = $userRepo;
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function createResetRequestForEmail(string $email): ?array
    {
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return null;
        }
        $existingRequests = $this->requestRepo->findActiveByUser($user);
        foreach ($existingRequests as $request) {
            $request->markUsed();
            $this->em->persist($request);
        }

        $raw = bin2hex(random_bytes(32));
        $hash = hash_hmac('sha256', $raw, $this->appSecret);
        $expires = new DateTimeImmutable(sprintf('+%d seconds', $this->ttlSeconds));

        $passwordResetRequest = new PasswordResetRequest($user, $hash, $expires);
        $this->em->persist($passwordResetRequest);
        $this->em->flush();

        return [
            'token' => $raw,
            'expiresAt' => $expires,
            'user' => $user,
        ];
    }

    /**
     * @throws NonUniqueResultException
     */
    public function validateTokenForUser(string $rawToken, $user = null): ?PasswordResetRequest
    {
        $hash = hash_hmac('sha256', $rawToken, $this->appSecret);

        if ($user !== null) {
            $candidate = $this->requestRepo->findLatestActiveForUser($user);
        } else {
            $candidate = $this->requestRepo->findLatestActiveByHash($hash);
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
     * @param PasswordResetRequest $request
     * @return void
     */
    public function markAsUsed(PasswordResetRequest $request): void
    {
        $request->markUsed();
        $this->em->persist($request);
        $this->em->flush();
    }
}
