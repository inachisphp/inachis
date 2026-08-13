<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Inachis\Diagnostics\Check\Database\SQLServerMemoryCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

if (!class_exists(TestSQLServerPlatform::class)) {
    abstract class TestSQLServerPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'sqlserver';
        }
    }
}

if (!class_exists(TestMySQLPlatform::class)) {
    abstract class TestMySQLPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'mysql';
        }
    }
}

final class SQLServerMemoryCheckTest extends TestCase
{
    private Connection&MockObject $connection;
    private SQLServerMemoryCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->check = new SQLServerMemoryCheck($this->connection);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('sqlsrv_max_memory', $this->check->getId());
        self::assertSame('max server memory', $this->check->getLabel());
        self::assertSame('Database', $this->check->getSection());
    }

    #[Test]
    public function itReturnsInfoResultForNonSqlServerPlatform(): void
    {
        $platform = $this->createMock(TestMySQLPlatform::class);
        $platform->method('getName')->willReturn('mysql');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $result = $this->check->run();

        self::assertSame('sqlsrv_max_memory', $this->getProperty($result, 'id'));
        self::assertSame('max server memory', $this->getProperty($result, 'label'));
        self::assertSame('info', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Max server memory check only applies to SQL Server.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Database', $this->getProperty($result, 'section'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsOkResultWhenMaxServerMemoryIsSufficient(): void
    {
        $platform = $this->createMock(TestSQLServerPlatform::class);
        $platform->method('getName')->willReturn('sqlserver');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with("SELECT value_in_use FROM sys.configurations WHERE name = 'max server memory (MB)'")
            ->willReturn([
                'value_in_use' => 4096,
            ]);

        $result = $this->check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('4096 MB', $this->getProperty($result, 'value'));
        self::assertSame('Max server memory is sufficient.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsWarningResultWhenMaxServerMemoryIsBelowRecommended(): void
    {
        $platform = $this->createMock(TestSQLServerPlatform::class);
        $platform->method('getName')->willReturn('sqlserver');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with("SELECT value_in_use FROM sys.configurations WHERE name = 'max server memory (MB)'")
            ->willReturn([
                'value_in_use' => 1024,
            ]);

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('1024 MB', $this->getProperty($result, 'value'));
        self::assertSame('Max server memory (1024 MB) below recommended (2048 MB).', $this->getMessageProperty($result));
        self::assertSame(
            'Increase max server memory in SQL Server configuration.',
            $this->getProperty($result, 'recommendation'),
        );
        self::assertSame('medium', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsErrorResultWhenDatabaseErrorOccurs(): void
    {
        $platform = $this->createMock(TestSQLServerPlatform::class);
        $platform->method('getName')->willReturn('sqlserver');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Connection timeout'));

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Could not connect to SQL Server: Connection timeout', $this->getMessageProperty($result));
        self::assertSame('Check database credentials and availability.', $this->getProperty($result, 'recommendation'));
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
