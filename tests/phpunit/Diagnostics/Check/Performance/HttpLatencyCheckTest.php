<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\HttpLatencyCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpLatencyCheckTest extends TestCase
{
    private HttpClientInterface&MockObject $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(HttpClientInterface::class);
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        $check = new HttpLatencyCheck($this->client);

        self::assertSame('http_latency', $check->getId());
        self::assertSame('HTTP Latency', $check->getLabel());
        self::assertSame('Environment', $check->getSection());
    }

    #[Test]
    public function itReturnsOkResultWhenLatencyIsLowAndNoIssuesDetected(): void
    {
        $check = new HttpLatencyCheck($this->client, 1, 2.0);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturn([
            'namelookup_time' => 0.001,
            'connect_time' => 0.002,
            'ssl_time' => 0.003,
            'starttransfer_time' => 0.010,
        ]);

        $this->client
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturn($response);

        $result = $check->run();

        self::assertSame('http_latency', $this->getProperty($result, 'id'));
        self::assertSame('HTTP Latency', $this->getProperty($result, 'label'));
        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertStringEndsWith('ms', (string) $this->getProperty($result, 'value'));
        self::assertStringContainsString('Internal avg:', (string) $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('Environment', $this->getProperty($result, 'section'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsWarningResultWhenColdStartLatencyIsDetected(): void
    {
        $check = new HttpLatencyCheck($this->client, 3, 5.0);

        // Internal request 1 (slow, cold start)
        $responseCold = $this->createMock(ResponseInterface::class);
        $responseCold->method('getStatusCode')->willReturn(200);
        $responseCold->method('getInfo')->willReturn([
            'namelookup_time' => 0.05,
            'connect_time' => 0.1,
            'ssl_time' => 0.1,
            'starttransfer_time' => 0.3,
        ]);

        // Fast follow-up responses
        $responseFast = $this->createMock(ResponseInterface::class);
        $responseFast->method('getStatusCode')->willReturn(200);
        $responseFast->method('getInfo')->willReturn([
            'namelookup_time' => 0.001,
            'connect_time' => 0.002,
            'ssl_time' => 0.003,
            'starttransfer_time' => 0.005,
        ]);

        $requestCount = 0;
        $this->client
            ->expects(self::exactly(6))
            ->method('request')
            ->willReturnCallback(function () use (&$requestCount, $responseCold, $responseFast) {
                ++$requestCount;
                if (1 === $requestCount) {
                    usleep(350000); // 350ms delay for cold start

                    return $responseCold;
                }

                usleep(5000); // 5ms delay for warm requests

                return $responseFast;
            });

        $result = $check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertStringContainsString('Cold start latency detected', (string) $this->getMessageProperty($result));
        self::assertSame('Investigate network, proxy, or container performance.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsWarningResultWhenHighProxyOverheadIsDetected(): void
    {
        $check = new HttpLatencyCheck($this->client, 1, 5.0);

        $responseInternal = $this->createMock(ResponseInterface::class);
        $responseInternal->method('getStatusCode')->willReturn(200);
        $responseInternal->method('getInfo')->willReturn([
            'namelookup_time' => 0.001,
            'connect_time' => 0.001,
            'ssl_time' => 0.001,
            'starttransfer_time' => 0.010,
        ]);

        $responsePublic = $this->createMock(ResponseInterface::class);
        $responsePublic->method('getStatusCode')->willReturn(200);
        $responsePublic->method('getInfo')->willReturn([
            'namelookup_time' => 0.050,
            'connect_time' => 0.050,
            'ssl_time' => 0.050,
            'starttransfer_time' => 0.250,
        ]);

        $this->client
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url) use ($responseInternal, $responsePublic) {
                if (str_contains($url, '127.0.0.1')) {
                    // Simulate fast internal response
                    usleep(10000); // 10ms

                    return $responseInternal;
                }

                // Simulate slow public proxy response
                usleep(200000); // 200ms

                return $responsePublic;
            });

        $result = $check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertStringContainsString('High reverse proxy overhead', (string) $this->getMessageProperty($result));
    }

    #[Test]
    public function itReturnsErrorResultWhenPublicLatencyIsHigh(): void
    {
        $check = new HttpLatencyCheck($this->client, 1, 5.0);

        $responseSlow = $this->createMock(ResponseInterface::class);
        $responseSlow->method('getStatusCode')->willReturn(200);
        $responseSlow->method('getInfo')->willReturn([
            'namelookup_time' => 0.1,
            'connect_time' => 0.2,
            'ssl_time' => 0.2,
            'starttransfer_time' => 1.1,
        ]);

        $this->client
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturnCallback(function () use ($responseSlow) {
                usleep(1100000); // 1.1s

                return $responseSlow;
            });

        $result = $check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertStringContainsString('High HTTP latency', (string) $this->getMessageProperty($result));
        self::assertSame('Investigate network, proxy, or container performance.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsErrorResultWhenHttpRequestFails(): void
    {
        $check = new HttpLatencyCheck($this->client, 1, 5.0);

        $this->client
            ->expects(self::once())
            ->method('request')
            ->willThrowException(new \RuntimeException('Network unreachable'));

        $result = $check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertSame('HTTP test failed', $this->getProperty($result, 'value'));
        self::assertSame('Network unreachable', $this->getMessageProperty($result));
        self::assertSame('Verify application availability.', $this->getProperty($result, 'recommendation'));
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
