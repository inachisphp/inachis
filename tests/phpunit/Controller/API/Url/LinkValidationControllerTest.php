<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Url {
    /**
     * Namespace function override for dns_get_record during PHPUnit execution.
     */
    function dns_get_record(string $hostname, int $type = \DNS_ANY, array &$authoritative_name_servers = [], array &$additional_records = [], bool $raw = false): array|false
    {
        if (isset($GLOBALS['mock_dns_records'][$hostname])) {
            return $GLOBALS['mock_dns_records'][$hostname];
        }

        return \dns_get_record($hostname, $type, $authoritative_name_servers, $additional_records, $raw);
    }
}

namespace Inachis\Tests\phpunit\Controller\API\Url {

    use Inachis\Controller\API\Url\LinkValidationController;
    use PHPUnit\Framework\Attributes\Test;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
    use Symfony\Contracts\HttpClient\HttpClientInterface;
    use Symfony\Contracts\HttpClient\ResponseInterface;

    final class LinkValidationControllerTest extends TestCase
    {
        private HttpClientInterface&MockObject $httpClient;
        private LinkValidationController $controller;

        protected function setUp(): void
        {
            parent::setUp();

            $this->httpClient = $this->createMock(HttpClientInterface::class);
            $this->controller = new LinkValidationController($this->httpClient);

            $_ENV['APP_ENV'] = 'dev';
            $GLOBALS['mock_dns_records'] = [];
        }

        protected function tearDown(): void
        {
            unset($_ENV['APP_ENV'], $GLOBALS['mock_dns_records']);
            parent::tearDown();
        }

        #[Test]
        public function itReturnsForbiddenWhenRefererHeaderIsMissing(): void
        {
            $request = Request::create('/incp/api/validate-links', 'POST');

            $response = ($this->controller)($request);

            self::assertSame(403, $response->getStatusCode());
            self::assertSame('{"error":"Missing referer"}', $response->getContent());
        }

        #[Test]
        public function itReturnsForbiddenWhenRefererHostDoesNotMatchRequestHost(): void
        {
            $request = Request::create('http://localhost/incp/api/validate-links', 'POST');
            $request->headers->set('referer', 'http://evil.com/some-page');

            $response = ($this->controller)($request);

            self::assertSame(403, $response->getStatusCode());
            self::assertSame('{"error":"Invalid referer"}', $response->getContent());
        }

        #[Test]
        public function itReturnsForbiddenWhenOriginHostDoesNotMatchRequestHost(): void
        {
            $request = Request::create('http://localhost/incp/api/validate-links', 'POST');
            $request->headers->set('referer', 'http://localhost/some-page');
            $request->headers->set('origin', 'http://evil.com');

            $response = ($this->controller)($request);

            self::assertSame(403, $response->getStatusCode());
            self::assertSame('{"error":"Invalid origin"}', $response->getContent());
        }

        #[Test]
        public function itReturnsBadRequestForInvalidPayload(): void
        {
            $request = $this->createValidRequest('invalid-json-content');

            $response = ($this->controller)($request);

            self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
            self::assertSame('{"error":"Invalid payload"}', $response->getContent());
        }

        #[Test]
        public function itValidatesRelativeLinkSuccessfully(): void
        {
            $request = $this->createValidRequest(json_encode([
                'links' => ['about-us'],
            ]));

            $responseMock = $this->createMock(ResponseInterface::class);
            $responseMock->method('getStatusCode')->willReturn(200);
            $responseMock->method('getInfo')->willReturn(['redirect_count' => 0]);
            $responseMock
                ->expects(self::once())
                ->method('getHeaders')
                ->with(false)
                ->willReturn([
                    'Content-Type' => ['text/html; charset=UTF-8'],
                ]);

            $this->httpClient
                ->expects(self::once())
                ->method('request')
                ->with('HEAD', 'http://localhost/about-us', [
                    'timeout' => 5,
                    'max_redirects' => 5,
                ])
                ->willReturn($responseMock);

            $response = ($this->controller)($request);

            self::assertSame(200, $response->getStatusCode());

            $data = json_decode((string) $response->getContent(), true);
            self::assertIsArray($data);
            self::assertCount(1, $data);
            self::assertSame('http://localhost/about-us', $data[0]['url']);
            self::assertTrue($data[0]['ok']);
            self::assertSame(200, $data[0]['status']);
            self::assertSame(['content-type' => 'text/html; charset=UTF-8'], $data[0]['headers']);
        }

        #[Test]
        public function itRetriesWithGetWhenHeadReturnsErrorStatus(): void
        {
            $request = $this->createValidRequest(json_encode([
                'links' => ['http://localhost/page'],
            ]));

            $headResponse = $this->createMock(ResponseInterface::class);
            $headResponse->method('getStatusCode')->willReturn(405);

            $getResponse = $this->createMock(ResponseInterface::class);
            $getResponse->method('getStatusCode')->willReturn(200);
            $getResponse->method('getInfo')->willReturn(['redirect_count' => 1]);
            $getResponse
                ->expects(self::once())
                ->method('getHeaders')
                ->with(false)
                ->willReturn([]);

            $this->httpClient
                ->expects(self::exactly(2))
                ->method('request')
                ->willReturnOnConsecutiveCalls($headResponse, $getResponse);

            $response = ($this->controller)($request);

            self::assertSame(200, $response->getStatusCode());

            $data = json_decode((string) $response->getContent(), true);
            self::assertIsArray($data);
            self::assertTrue($data[0]['ok']);
            self::assertSame(200, $data[0]['status']);
            self::assertSame(1, $data[0]['redirects']);
        }

        #[Test]
        public function itHandlesHttpClientExceptionsGracefully(): void
        {
            $request = $this->createValidRequest(json_encode([
                'links' => ['http://localhost/failing-endpoint'],
            ]));

            $exception = new class('Connection timed out') extends \RuntimeException implements ExceptionInterface {};

            $this->httpClient
                ->expects(self::once())
                ->method('request')
                ->willThrowException($exception);

            $response = ($this->controller)($request);

            self::assertSame(200, $response->getStatusCode());

            $data = json_decode((string) $response->getContent(), true);
            self::assertIsArray($data);
            self::assertFalse($data[0]['ok']);
            self::assertNull($data[0]['status']);
            self::assertSame('Connection timed out', $data[0]['error']);
        }

        #[Test]
        public function itBlocksPrivateNetworkIpsInProductionEnvironment(): void
        {
            $_ENV['APP_ENV'] = 'prod';
            $GLOBALS['mock_dns_records']['private.example.com'] = [
                ['ip' => '127.0.0.1'],
            ];

            $request = $this->createValidRequest(json_encode([
                'links' => ['http://private.example.com/status'],
            ]));

            $response = ($this->controller)($request);

            self::assertSame(200, $response->getStatusCode());

            $data = json_decode((string) $response->getContent(), true);
            self::assertIsArray($data);
            self::assertFalse($data[0]['ok']);
            self::assertNull($data[0]['status']);
            self::assertSame('Blocked (private network)', $data[0]['error']);
        }

        private function createValidRequest(string $content): Request
        {
            $request = Request::create('http://localhost/incp/api/validate-links', 'POST', [], [], [], [], $content);
            $request->headers->set('referer', 'http://localhost/admin/dashboard');
            $request->headers->set('origin', 'http://localhost');

            return $request;
        }
    }
}
