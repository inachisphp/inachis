<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\User;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use App\Service\User\PasswordResetTokenService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class PasswordResetTokenServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private PasswordResetRequestRepository $requestRepository;
    private UserRepository $userRepository;
    private PasswordResetTokenService $tokenService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->requestRepository = $this->createStub(PasswordResetRequestRepository::class);
        $this->userRepository = $this->createStub(UserRepository::class);
    }

    /**
     * @throws RandomException
     */
    public function testCreateResetRequestForEmailWhenUserNotFound(): void
    {
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertNull($this->tokenService->createResetRequestForEmail('test@example.com'));
    }

    /**
     * @throws RandomException
     */
    public function testCreateResetRequestForEmail(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $request = new PasswordResetRequest($user, 'tokenHash', new DateTimeImmutable('now'));
        $this->userRepository
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        $this->requestRepository
            ->method('findActiveByUser')
            ->with($user)
            ->willReturn([$request]);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $tokenResult = $this->tokenService->createResetRequestForEmail('test@example.com');
        $this->assertIsString($tokenResult['token']);
        $this->assertEquals(64, strlen($tokenResult['token']));
        $this->assertTrue(ctype_xdigit($tokenResult['token']));
        $this->assertEquals($user, $tokenResult['user']);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testValidateTokenForUserWithValidCandidate(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $candidate = new PasswordResetRequest($user, hash_hmac('sha256', 'raw-token', 'secret'), new DateTimeImmutable('tomorrow'));
        $this->requestRepository
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertEquals($candidate, $this->tokenService->validateTokenForUser('raw-token', $user));
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testValidateTokenForUserByHash(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $hash = hash_hmac('sha256', 'raw-token', 'secret');
        $candidate = new PasswordResetRequest($user, $hash, new DateTimeImmutable('tomorrow'));
        $this->requestRepository
            ->method('findLatestActiveByHash')
            ->with($hash)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertEquals($candidate, $this->tokenService->validateTokenForUser('raw-token', null));
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testValidateTokenForUserCandidateNotFound(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $candidate = null;
        $this->requestRepository
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertNull($this->tokenService->validateTokenForUser('raw-token', $user));
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testValidateTokenForUserWithExpiredToken(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $candidate = new PasswordResetRequest($user, 'tokenHash', new DateTimeImmutable('yesterday'));
        $this->requestRepository
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertNull($this->tokenService->validateTokenForUser('raw-token', $user));
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testValidateTokenForUserWithInvalidCandidate(): void
    {
        $user = new User('user', 'password', 'test@example.com');
        $candidate = new PasswordResetRequest($user, 'tokenHash', new DateTimeImmutable('tomorrow'));
        $this->requestRepository
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->assertNull($this->tokenService->validateTokenForUser('raw-token', $user));
    }

    public function testMarkAsUsed():void
    {
        $user = new User('user', 'password', 'test@example.com');
        $request = new PasswordResetRequest($user, 'tokenHash', new DateTimeImmutable('now'));
        $this->entityManager
            ->method('persist')
            ->with($request);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->entityManager,
            $this->requestRepository,
            $this->userRepository
        );
        $this->tokenService->markAsUsed($request);
        $this->assertTrue($request->isUsed());
    }
}
