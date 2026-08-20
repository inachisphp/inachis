<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Inachis\Diagnostics\Check\Database\CharsetCollationCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class TestMySQLPlatform extends AbstractPlatform
{
    public function getName(): string
    {
        return 'mysql';
    }
}

abstract class TestPostgreSQLPlatform extends AbstractPlatform
{
    public function getName(): string
    {
        return 'postgresql';
    }
}

final class CharsetCollationCheckTest extends TestCase
{
    private Connection&MockObject $connection;
    private CharsetCollationCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->check = new CharsetCollationCheck($this->connection);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('db_charset_collation', $this->check->getId());
        self::assertSame('Character set / Collation', $this->check->getLabel());
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

        self::assertSame('db_charset_collation', $this->getProperty($result, 'id'));
        self::assertSame('Character set / Collation', $this->getProperty($result, 'label'));
        self::assertSame('info', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Charset check only applies to MySQL/MariaDB.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Database', $this->getProperty($result, 'section'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsOkResultWhenCharsetAndCollationAreUtf8mb4(): void
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
            ->with('SELECT @@character_set_database AS charset, @@collation_database AS collation')
            ->willReturn([
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]);

        $result = $this->check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('Charset: utf8mb4, Collation: utf8mb4_unicode_ci', $this->getProperty($result, 'value'));
        self::assertSame('Charset: utf8mb4, Collation: utf8mb4_unicode_ci', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('low', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsWarningResultWhenCharsetIsNotUtf8mb4(): void
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
            ->with('SELECT @@character_set_database AS charset, @@collation_database AS collation')
            ->willReturn([
                'charset' => 'latin1',
                'collation' => 'latin1_swedish_ci',
            ]);

        $result = $this->check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('Charset: latin1, Collation: latin1_swedish_ci', $this->getProperty($result, 'value'));
        self::assertSame('Charset: latin1, Collation: latin1_swedish_ci', $this->getMessageProperty($result));
        self::assertSame('Use utf8mb4 character set and compatible collation for modern apps.', $this->getProperty($result, 'recommendation'));
        self::assertSame('medium', $this->getProperty($result, 'severity'));
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
            ->willThrowException(new \RuntimeException('Database connection lost'));

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('Could not retrieve charset/collation: Database connection lost', $this->getMessageProperty($result));
        self::assertSame('Ensure database is running and credentials are correct.', $this->getProperty($result, 'recommendation'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    private function getProperty(object $object, string $propertyName): mixed
    {
        $ref = new \ReflectionClass($object);

        if ($ref->hasProperty($propertyName)) {
            return $ref->getProperty($propertyName)->getValue($object);
        }

        if ($ref->hasMethod('__construct')) {
            $params = $ref->getMethod('__construct')->getParameters();
            foreach ($params as $index => $param) {
                if ($param->getName() === $propertyName) {
                    $props = $ref->getProperties();
                    if (isset($props[$index])) {
                        return $props[$index]->getValue($object);
                    }
                }
            }
        }

        if ('severity' === $propertyName) {
            foreach ($ref->getProperties() as $prop) {
                $val = $prop->getValue($object);
                if (in_array($val, ['low', 'medium', 'high', 'critical'], true)) {
                    return (string) $val;
                }
            }
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

        foreach ($ref->getProperties() as $prop) {
            $val = $prop->getValue($object);
            if (is_string($val) && (str_contains($val, 'Charset') || str_contains($val, 'Could not retrieve'))) {
                return $val;
            }
        }

        return null;
    }
}
