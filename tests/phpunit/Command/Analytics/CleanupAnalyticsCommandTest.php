<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Analytics;

use Doctrine\DBAL\Connection;
use Inachis\Command\Analytics\CleanupAnalyticsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupAnalyticsCommandTest extends TestCase
{
    public function testExecuteRemovesOldAnalyticsData(): void
    {
        $projectDir = sys_get_temp_dir().'/inachis-cleanup-'.uniqid();

        mkdir($projectDir.'/var/analytics', 0775, true);

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->exactly(3))
            ->method('executeStatement')
            ->with(
                $this->callback(static fn (string $sql): bool => str_contains($sql, 'DELETE FROM analytics_page_view')
                    || str_contains($sql, 'DELETE FROM analytics_unique_visitor')
                    || str_contains($sql, 'DELETE FROM analytics_security_event'),
                ),
                $this->callback(static fn (array $params): bool => isset($params['days']),
                ),
            );

        $command = new CleanupAnalyticsCommand(
            $projectDir,
            $connection,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(
            Command::SUCCESS,
            $exitCode,
        );

        $display = $tester->getDisplay();

        self::assertStringContainsString(
            'Removing analytics data older than 90 days',
            $display,
        );

        self::assertStringContainsString(
            'Removing processed log files older than 7 days',
            $display,
        );

        $this->removeDirectory($projectDir);
    }

    public function testExecuteSucceedsWhenAnalyticsDirectoryIsEmpty(): void
    {
        $projectDir = sys_get_temp_dir().'/inachis-cleanup-empty-'.uniqid();

        mkdir($projectDir.'/var/analytics', 0775, true);

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->exactly(3))
            ->method('executeStatement');

        $command = new CleanupAnalyticsCommand(
            $projectDir,
            $connection,
        );

        $tester = new CommandTester($command);

        self::assertSame(
            Command::SUCCESS,
            $tester->execute([]),
        );

        $this->removeDirectory($projectDir);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
