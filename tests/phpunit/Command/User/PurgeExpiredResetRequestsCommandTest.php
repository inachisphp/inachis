<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Command\User;

use Inachis\Command\User\PurgeExpiredResetRequestsCommand;
use Inachis\Repository\User\PasswordResetRequestRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PurgeExpiredResetRequestsCommandTest extends TestCase
{
    public function testExecuteCreatesAdminUserSuccessfully(): void
    {
        $passwordResetRequestRepository = $this->createMock(PasswordResetRequestRepository::class);
        $passwordResetRequestRepository
            ->expects($this->once())
            ->method('purgeExpiredHashes')
            ->willReturn(3);
        $command = new PurgeExpiredResetRequestsCommand($passwordResetRequestRepository);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Deleted 3 expired password reset requests.', $output);
        $this->assertSame(0, $tester->getStatusCode());
    }
}
