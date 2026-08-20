<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\SmtpConnectionCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;

if (!interface_exists(TestPingableTransportInterface::class)) {
    interface TestPingableTransportInterface extends TransportInterface
    {
        public function ping(): void;
    }
}

final class SmtpConnectionCheckTest extends TestCase
{
    private TransportInterface&MockObject $transport;
    private SmtpConnectionCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = $this->createMock(TestPingableTransportInterface::class);
        $this->check = new SmtpConnectionCheck($this->transport);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('smtp_connection', $this->check->getId());
        self::assertSame('SMTP Connection', $this->check->getLabel());
        self::assertSame('Environment', $this->check->getSection());
    }

    #[Test]
    public function itReturnsOkResultWhenTransportPingsSuccessfully(): void
    {
        /** @var TestPingableTransportInterface&MockObject $transport */
        $transport = $this->transport;
        $transport->expects(self::once())->method('ping');

        $result = $this->check->run();

        self::assertSame('smtp_connection', $this->getProperty($result, 'id'));
        self::assertSame('SMTP Connection', $this->getProperty($result, 'label'));
        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('Connected', $this->getProperty($result, 'value'));
        self::assertSame('SMTP transport reachable.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Environment', $this->getProperty($result, 'section'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsOkResultWhenTransportDoesNotHavePingMethod(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $check = new SmtpConnectionCheck($transport);

        $result = $check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('Connected', $this->getProperty($result, 'value'));
        self::assertSame('SMTP transport reachable.', $this->getMessageProperty($result));
    }

    #[Test]
    public function itReturnsErrorResultWhenTransportExceptionIsThrown(): void
    {
        $exception = new TransportException('Connection refused');

        /** @var TestPingableTransportInterface&MockObject $transport */
        $transport = $this->transport;
        $transport->expects(self::once())
            ->method('ping')
            ->willThrowException($exception);

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertSame('Connection failed', $this->getProperty($result, 'value'));
        self::assertSame('Connection refused', $this->getMessageProperty($result));
        self::assertSame('Verify MAILER_DSN configuration.', $this->getProperty($result, 'recommendation'));
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
