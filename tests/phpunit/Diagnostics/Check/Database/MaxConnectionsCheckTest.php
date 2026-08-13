<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Inachis\Diagnostics\Check\Database\MaxConnectionsCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

if (!class_exists(TestMySQLPlatform::class)) {
    abstract class TestMySQLPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'mysql';
        }
    }
}

if (!class_exists(TestSQLServerPlatform::class)) {
    abstract class TestSQLServerPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'sqlserver';
        }
    }
}

if (!class_exists(TestUnsupportedPlatform::class)) {
    abstract class TestUnsupportedPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'sqlite';
        }
    }
}

final class MaxConnectionsCheckTest extends TestCase
{
    private Connection&MockObject $connection;
    private MaxConnectionsCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->check = new MaxConnectionsCheck($this->connection);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('db_max_connections', $this->check->getId());
        self::assertSame('max_connections / max worker threads', $this->check->getLabel());
        self::assertSame('Database', $this->check->getSection());
    }

    #[Test]
    public function itReturnsOkResultWhenMySqlMaxConnectionsIsSufficient(): void
    {
        $platform = $this->createMock(TestMySQLPlatform::class);
        $platform->method('getName')->willReturn('mysql');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with("SHOW VARIABLES LIKE 'max_connections'")
            ->willReturn([
                'Variable_name' => 'max_connections',
                'Value' => '150',
            ]);

        $result = $this->check->run();

        self::assertSame('db_max_connections', $this->getProperty($result, 'id'));
        self::assertSame('max_connections / max worker threads', $this->getProperty($result, 'label'));
        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('150', $this->getProperty($result, 'value'));
        self::assertSame('Max connections/workers (150) is sufficient.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Database', $this->getProperty($result, 'section'));
    }

    #[Test]
    public function itReturnsWarningResultWhenMySqlMaxConnectionsIsBelowRecommended(): void
    {
        $platform = $this->createMock(TestMySQLPlatform::class);
        $platform->method('getName')->willReturn('mysql');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with("SHOW VARIABLES LIKE 'max_connections'")
            ->willReturn([
                'Variable_name' => 'max_connections',
                'Value' => '50',
            ]);

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('50', $this->getProperty($result, 'value'));
        self::assertSame('Max connections/workers (50) below recommended (100).', $this->getMessageProperty($result));
        self::assertSame(
            'Increase max connections / worker threads according to your DB platform recommendations.',
            $this->getProperty($result, 'recommendation'),
        );
    }

    #[Test]
    public function itReturnsOkResultWhenSqlServerMaxWorkerThreadsIsSufficient(): void
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
            ->with("SELECT value_in_use FROM sys.configurations WHERE name = 'max worker threads'")
            ->willReturn([
                'value_in_use' => 512,
            ]);

        $result = $this->check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('512', $this->getProperty($result, 'value'));
        self::assertSame('Max connections/workers (512) is sufficient.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsWarningResultWhenSqlServerMaxWorkerThreadsIsBelowRecommended(): void
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
            ->with("SELECT value_in_use FROM sys.configurations WHERE name = 'max worker threads'")
            ->willReturn([
                'value_in_use' => 128,
            ]);

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('128', $this->getProperty($result, 'value'));
        self::assertSame('Max connections/workers (128) below recommended (256).', $this->getMessageProperty($result));
        self::assertSame(
            'Increase max connections / worker threads according to your DB platform recommendations.',
            $this->getProperty($result, 'recommendation'),
        );
    }

    #[Test]
    public function itReturnsInfoResultForUnsupportedPlatform(): void
    {
        $platform = $this->createMock(TestUnsupportedPlatform::class);
        $platform->method('getName')->willReturn('sqlite');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $result = $this->check->run();

        self::assertSame('info', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Database platform not supported for this check.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsErrorResultWhenQueryFails(): void
    {
        $platform = $this->createMock(TestMySQLPlatform::class);
        $platform->method('getName')->willReturn('mysql');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willThrowException(new \RuntimeException('Connection lost'));

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Could not connect to database: Connection lost', $this->getMessageProperty($result));
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
