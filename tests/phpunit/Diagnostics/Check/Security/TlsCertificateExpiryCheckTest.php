<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\TlsCertificateExpiryCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class TlsCertificateExpiryCheckTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private TlsCertificateExpiryCheck $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->check = new TlsCertificateExpiryCheck($this->requestStack);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        self::assertSame('tls_certificate_expiry', $this->check->getId());
        self::assertSame('TLS Certificate Expiry', $this->check->getLabel());
        self::assertSame('Security', $this->check->getSection());
    }

    #[Test]
    public function itReturnsErrorResultWhenNoMainRequestIsAvailable(): void
    {
        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        $result = $this->check->run();

        self::assertSame('tls_certificate_expiry', $this->getProperty($result, 'id'));
        self::assertSame('TLS Certificate Expiry', $this->getProperty($result, 'label'));
        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertSame('No active request available.', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Security', $this->getProperty($result, 'section'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsErrorResultWhenSocketConnectionFails(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getHost')
            ->willReturn('127.0.0.1');

        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $result = $this->check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertNull($this->getProperty($result, 'value'));
        self::assertStringContainsString('Unable to check certificate:', (string) $this->getMessageProperty($result));
        self::assertSame('Verify TLS connectivity and certificate configuration.', $this->getProperty($result, 'recommendation'));
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
