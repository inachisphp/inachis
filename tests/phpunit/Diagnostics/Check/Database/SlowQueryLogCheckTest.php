<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Inachis\Diagnostics\Check\Database\SlowQueryLogCheck;
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

if (!class_exists(TestPostgreSQLPlatform::class)) {
    abstract class TestPostgreSQLPlatform extends AbstractPlatform
    {
        public function getName(): string
        {
            return 'postgresql';
        }
    }
}

final class SlowQueryLogCheckTest extends TestCase
{
    private Connection&MockObject $connection;
    private SlowQueryLogCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->check = new SlowQueryLogCheck($this->connection);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('slow_query_log', $this->check->getId());
        self::assertSame('slow_query_log', $this->check->getLabel());
        self::assertSame('Database', $this->check->getSection());
    }

    #[Test]
    public function itReturnsInfoResultForNonMySQLPlatform(): void
    {
        $platform = $this->createMock(TestPostgreSQLPlatform::class);
        $platform->method('getName')->willReturn('postgresql');

        $this->connection
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $result = $this->check->run();

        self::assertSame('slow_query_log', $this->getProperty($result, 'id'));
        self::assertSame('slow_query_log', $this->getProperty($result, 'label'));
        self::assertSame('info', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Slow query log check only applies to MySQL/MariaDB.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Database', $this->getProperty($result, 'section'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsOkResultWhenSlowQueryLogIsEnabled(): void
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
            ->with("SHOW VARIABLES LIKE 'slow_query_log'")
            ->willReturn([
                'Variable_name' => 'slow_query_log',
                'Value' => 'ON',
            ]);

        $result = $this->check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('enabled', $this->getProperty($result, 'value'));
        self::assertSame('Slow query log is enabled.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsInfoResultWhenSlowQueryLogIsDisabled(): void
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
            ->with("SHOW VARIABLES LIKE 'slow_query_log'")
            ->willReturn([
                'Variable_name' => 'slow_query_log',
                'Value' => 'OFF',
            ]);

        $result = $this->check->run();

        self::assertSame('info', $this->getProperty($result, 'status'));
        self::assertSame('disabled', $this->getProperty($result, 'value'));
        self::assertSame('Slow query log is disabled; enable for monitoring.', $this->getMessageProperty($result));
        self::assertSame('Enable slow_query_log to detect slow queries and optimize them.', $this->getProperty($result, 'recommendation'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsErrorResultWhenDatabaseErrorOccurs(): void
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
            ->willThrowException(new \RuntimeException('Connection failed'));

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Could not retrieve slow_query_log: Connection failed', $this->getMessageProperty($result));
        self::assertSame('Ensure database is running and credentials are correct.', $this->getProperty($result, 'recommendation'));
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
