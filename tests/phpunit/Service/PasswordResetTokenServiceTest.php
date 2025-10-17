<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service;

use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use App\Service\PasswordResetTokenService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

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
        $this->tokenService = new ContentImageUpdater($this->em, $this->requestRepo, $this->userRepo);
    }

    public function testCreateResetRequestForEmail(): void
    {
    }
}
