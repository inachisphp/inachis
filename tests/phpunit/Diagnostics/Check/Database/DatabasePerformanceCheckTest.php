<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Inachis\Diagnostics\Check\Database\DatabasePerformanceCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class TestUnsupportedPlatform extends AbstractPlatform
{
    public function getName(): string
    {
        return 'sqlite';
    }
}

final class DatabasePerformanceCheckTest extends TestCase
{
    private Connection&MockObject $connection;
    private DatabasePerformanceCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->check = new DatabasePerformanceCheck($this->connection);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('database_health', $this->check->getId());
        self::assertSame('Database Health', $this->check->getLabel());
        self::assertSame('Database', $this->check->getSection());
    }

    #[Test]
    public function itRunsMySqlHealthCheckSuccessfullyWhenHealthy(): void
    {
        $platform = $this->createMock(MySQLPlatform::class);

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::exactly(6))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['Variable_name' => 'Threads_running', 'Value' => '10'],
                ['Variable_name' => 'max_connections', 'Value' => '100'],
                ['Value' => '0'],
                ['Variable_name' => 'Innodb_deadlocks', 'Value' => '0'],
                ['Variable_name' => 'Innodb_row_lock_current_waits', 'Value' => '0'],
                false,
            );

        $result = $this->check->run();

        self::assertSame('database_health', $this->getProperty($result, 'id'));
        self::assertSame('Database Health', $this->getProperty($result, 'label'));
        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('10%', $this->getProperty($result, 'value'));
        self::assertSame('Healthy', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Database', $this->getProperty($result, 'section'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itRunsMySqlHealthCheckAndDetectsIssues(): void
    {
        $platform = $this->createMock(MariaDBPlatform::class);

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::exactly(6))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['Variable_name' => 'Threads_running', 'Value' => '85'],
                ['Variable_name' => 'max_connections', 'Value' => '100'],
                ['Value' => '6'],
                ['Variable_name' => 'Innodb_deadlocks', 'Value' => '2'],
                ['Variable_name' => 'Innodb_row_lock_current_waits', 'Value' => '3'],
                ['Seconds_Behind_Master' => '35'],
            );

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertSame('85%', $this->getProperty($result, 'value'));

        $message = $this->getMessageProperty($result);
        self::assertNotNull($message);
        self::assertStringContainsString('High connection usage (85%)', $message);
        self::assertStringContainsString('6 long-running queries', $message);
        self::assertStringContainsString('2 InnoDB deadlocks detected', $message);
        self::assertStringContainsString('3 row lock waits', $message);
        self::assertStringContainsString('Replication lag: 35s', $message);
        self::assertSame('Database experiencing stress conditions.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itRunsPostgresHealthCheckSuccessfullyWhenHealthy(): void
    {
        $platform = $this->createMock(PostgreSQLPlatform::class);

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::exactly(5))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls('5', '100', '0', '0', '0');

        $result = $this->check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('5%', $this->getProperty($result, 'value'));
        self::assertSame('Healthy', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itRunsPostgresHealthCheckAndDetectsElevatedStress(): void
    {
        $platform = $this->createMock(PostgreSQLPlatform::class);

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::exactly(5))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls('65', '100', '2', '1', '10');

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('65%', $this->getProperty($result, 'value'));

        $message = $this->getMessageProperty($result);
        self::assertNotNull($message);
        self::assertStringContainsString('Elevated connection usage (65%)', $message);
        self::assertStringContainsString('2 long-running queries', $message);
        self::assertStringContainsString('1 deadlocks detected', $message);
        self::assertStringContainsString('Replication lag: 10s', $message);
        self::assertSame('Database experiencing stress conditions.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsWarningForUnsupportedPlatforms(): void
    {
        $platform = $this->createMock(TestUnsupportedPlatform::class);

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('Unsupported', $this->getProperty($result, 'value'));
        self::assertStringContainsString('not supported.', (string) $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('medium', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsErrorWhenDbalExceptionIsThrown(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willThrowException($this->createMock(ConnectionException::class));

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertSame('Query failed', $this->getProperty($result, 'value'));
        self::assertSame('Verify DB permissions.', $this->getProperty($result, 'recommendation'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    private function getProperty(object $object, string $propertyName): mixed
    {
        $ref = new \ReflectionClass($object);

        if ($ref->hasProperty($propertyName)) {
            return $ref->getProperty($propertyName)->getValue($object);
        }

        $props = array_values($ref->getProperties());

        if ($ref->hasMethod('__construct')) {
            $params = array_values($ref->getMethod('__construct')->getParameters());
            foreach ($params as $index => $param) {
                if ($param->getName() === $propertyName && isset($props[$index])) {
                    return $props[$index]->getValue($object);
                }
            }
        }

        if ('severity' === $propertyName && isset($props[7])) {
            return $props[7]->getValue($object);
        }

        return null;
    }

    private function getMessageProperty(object $object): ?string
    {
        $ref = new \ReflectionClass($object);

        foreach (['message', 'description', 'details', 'summary', 'text'] as $propertyName) {
            if ($ref->hasProperty($propertyName)) {
                $val = $ref->getProperty($propertyName)->getValue($object);
                if (null !== $val) {
                    return (string) $val;
                }
            }
        }

        $props = array_values($ref->getProperties());

        if (isset($props[4])) {
            $val = $props[4]->getValue($object);
            if (null !== $val) {
                return (string) $val;
            }
        }

        return null;
    }
}
