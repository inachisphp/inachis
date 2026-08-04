<?php

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Command\Analytics;

use Doctrine\DBAL\Connection;
use Inachis\Command\Analytics\AggregateAnalyticsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AggregateAnalyticsCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenNoAnalyticsDirectoryExists(): void
    {
        $connection = $this->createMock(Connection::class);

        $command = new AggregateAnalyticsCommand($connection);

        $tester = new CommandTester($command);

        self::assertSame(
            Command::SUCCESS,
            $tester->execute([])
        );
    }
}
