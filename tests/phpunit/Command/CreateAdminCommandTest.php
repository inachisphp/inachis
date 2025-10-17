<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Command;

use App\Command\CreateAdminCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends TestCase
{
    private $em;
    private $passwordHasher;

    public function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
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
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) use ($hashedPassword) {
                $this->assertEquals('testuser', $user->getUsername());
                $this->assertEquals('test@example.com', $user->getEmail());
                $this->assertEquals($hashedPassword, $user->getPassword());
                $this->assertEquals('testuser', $user->getDisplayName());
                return true;
            }));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateAdminCommand($this->em, $this->passwordHasher);
        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper
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
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');
        $this->passwordHasher->expects($this->never())->method('hashPassword');

        $command = new CreateAdminCommand($this->em, $this->passwordHasher);
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
        $tester = new CommandTester($command);
        $tester->setInputs(['testuser', 'test@example.com', '']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Aborted.');

        $tester->execute([]);
    }

    public function testExecuteValidatorReturnsValueForNonEmptyPassword(): void
    {
        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_secret');
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');
        $command = new CreateAdminCommand($this->em, $this->passwordHasher);
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
