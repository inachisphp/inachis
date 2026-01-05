<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Command;

use Inachis\Command\PurgeExpiredResetRequestsCommand;
use Inachis\Repository\PasswordResetRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PurgeExpiredResetRequestsCommandTest extends TestCase
{
    private $entityManager;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }
    public function testExecuteCreatesAdminUserSuccessfully(): void
    {
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $passwordResetRequestRepository
            ->expects($this->once())
            ->method('purgeExpiredHashes')
            ->willReturn(3);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($passwordResetRequestRepository);
        $command = new PurgeExpiredResetRequestsCommand($this->entityManager);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Deleted 3 expired password reset requests.', $output);
        $this->assertSame(0, $tester->getStatusCode());
    }
}
