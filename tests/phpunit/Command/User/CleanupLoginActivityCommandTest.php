<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\User;

use Inachis\Command\User\CleanupLoginActivityCommand;
use Inachis\Message\CleanupLoginActivityMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CleanupLoginActivityCommandTest extends TestCase
{
    #[Test]
    public function itDispatchesCleanupMessageWithDefaultOptions(): void
    {
        $messenger = $this->createMock(MessageBusInterface::class);
        $messenger
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(
                fn (mixed $message): bool => $message instanceof CleanupLoginActivityMessage
                    && $this->getMessageProperty($message, 'dryRun') === false
                    && $this->getMessageProperty($message, 'batchSize') === 1000
            ))
            ->willReturnCallback(fn (object $message): Envelope => new Envelope($message));

        $command = new CleanupLoginActivityCommand($messenger);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $tester->getDisplay();
        self::assertStringContainsString('Cleanup job dispatched to Messenger.', $display);
        self::assertStringContainsString('messenger:consume async', $display);
    }

    #[Test]
    public function itDispatchesCleanupMessageWithDryRunOption(): void
    {
        $messenger = $this->createMock(MessageBusInterface::class);
        $messenger
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(
                fn (mixed $message): bool => $message instanceof CleanupLoginActivityMessage
                    && $this->getMessageProperty($message, 'dryRun') === true
                    && $this->getMessageProperty($message, 'batchSize') === 1000
            ))
            ->willReturnCallback(fn (object $message): Envelope => new Envelope($message));

        $command = new CleanupLoginActivityCommand($messenger);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $tester->getDisplay();
        self::assertStringContainsString('Cleanup job dispatched to Messenger.', $display);
    }

    private function getMessageProperty(CleanupLoginActivityMessage $message, string $propertyName): mixed
    {
        $getter = 'is' . ucfirst($propertyName);
        if (method_exists($message, $getter)) {
            return $message->{$getter}();
        }

        $getter = 'get' . ucfirst($propertyName);
        if (method_exists($message, $getter)) {
            return $message->{$getter}();
        }

        $reflection = new \ReflectionClass($message);
        if ($reflection->hasProperty($propertyName)) {
            return $reflection->getProperty($propertyName)->getValue($message);
        }

        return null;
    }
}
