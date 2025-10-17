<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use App\Service\PasswordResetTokenService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class PasswordResetTokenServiceTest extends TestCase
{
    private EntityManagerInterface $em;

    private PasswordResetRequestRepository $requestRepo;
    private UserRepository $userRepo;
    private PasswordResetTokenService $tokenService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->requestRepo = $this->createMock(PasswordResetRequestRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
    }

    /**
     * @throws RandomException
     */
    public function testCreateResetRequestForEmailWhenUserNotFound(): void
    {
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        $this->requestRepo->expects($this->once())
            ->method('findActiveByUser')
            ->with($user)
            ->willReturn([$request]);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->requestRepo->expects($this->once())
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->requestRepo->expects($this->once())
            ->method('findLatestActiveByHash')
            ->with($hash)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->requestRepo->expects($this->once())
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->requestRepo->expects($this->once())
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
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
        $this->requestRepo->expects($this->once())
            ->method('findLatestActiveForUser')
            ->with($user)
            ->willReturn($candidate);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
        );
        $this->assertNull($this->tokenService->validateTokenForUser('raw-token', $user));
    }

    public function testMarkAsUsed():void
    {
        $user = new User('user', 'password', 'test@example.com');
        $request = new PasswordResetRequest($user, 'tokenHash', new DateTimeImmutable('now'));
        $this->em->expects($this->once())
            ->method('persist')
            ->with($request);
        $this->tokenService = new PasswordResetTokenService(
            'secret',
            $this->em,
            $this->requestRepo,
            $this->userRepo
        );
        $this->tokenService->markAsUsed($request);
        $this->assertTrue($request->isUsed());
    }
}
