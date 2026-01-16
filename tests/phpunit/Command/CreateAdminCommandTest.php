<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Command;

use Inachis\Command\CreateAdminCommand;
use Inachis\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends TestCase
{
    private $entityManager;
    private $passwordHasher;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
    }
    public function testExecuteCreatesAdminUserSuccessfully(): void
    {
        $hashedPassword = 'hashed_secret';
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plain_secret')
            ->willReturn($hashedPassword);
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) use ($hashedPassword) {
                $this->assertEquals('testuser', $user->getUsername());
                $this->assertEquals('test@example.com', $user->getEmail());
                $this->assertEquals($hashedPassword, $user->getPassword());
                $this->assertEquals('testuser', $user->getDisplayName());
                return true;
            }));
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateAdminCommand($this->entityManager, $this->passwordHasher);
        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper
            ->expects($this->atMost(3))
            ->method('ask')
            ->willReturnOnConsecutiveCalls('testuser', 'test@example.com', 'plain_secret');
        $command->setHelperSet(new HelperSet(['question' => $questionHelper]));
        $tester = new CommandTester($command);
        $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('User testuser created', $output);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteThrowsExceptionWhenPasswordIsEmpty(): void
    {
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
        $this->passwordHasher->expects($this->never())->method('hashPassword');

        $command = new CreateAdminCommand($this->entityManager, $this->passwordHasher);
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
        $tester = new CommandTester($command);
        $tester->setInputs(['test-user', 'test@example.com', '' ,'']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The password cannot be empty');
        $tester->execute([]);
    }

    public function testExecuteValidatorReturnsValueForNonEmptyPassword(): void
    {
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_secret');
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->once())->method('flush');
        $command = new CreateAdminCommand($this->entityManager, $this->passwordHasher);
        $command->setHelperSet(new HelperSet([
            'question' => new QuestionHelper(),
        ]));
        $tester = new CommandTester($command);
        $tester->setInputs([
            'testuser',
            'test@example.com',
            'nonempty',
        ]);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('User testuser created', $tester->getDisplay());
    }

}
