<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Url;

use Inachis\Controller\API\Url\LinkValidationController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Link Validation Controller tests.
 */
final class LinkValidationControllerTest extends TestCase
{
    private const BASE_URL = 'https://93.184.216.34';

    private const HOST = 'example.com';

    /**
     * Reject a request without a referer.
     */
    #[Test]
    public function itRejectsRequestWithoutReferer(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            ['links' => []],
            [],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Missing referer'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject a request with an invalid referer.
     */
    #[Test]
    public function itRejectsRequestWithInvalidReferer(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            ['links' => []],
            [
                'referer' => 'https://evil.example.com/',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Invalid referer'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Accept a request with a matching referer.
     */
    #[Test]
    public function itAcceptsRequestWithMatchingReferer(): void
    {
        $controller = $this->createController(
            new MockHttpClient(
                new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]),
            ),
        );

        $request = $this->createRequest(
            ['links' => []],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame([], $this->decodeResponse($response));
    }

    /**
     * Reject a request with an invalid origin.
     */
    #[Test]
    public function itRejectsRequestWithInvalidOrigin(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            ['links' => []],
            [
                'referer' => 'https://example.com/admin',
                'origin' => 'https://evil.example.com',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Invalid origin'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Accept a request with a matching origin.
     */
    #[Test]
    public function itAcceptsRequestWithMatchingOrigin(): void
    {
        $controller = $this->createController(
            new MockHttpClient(
                new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]),
            ),
        );

        $request = $this->createRequest(
            ['links' => []],
            [
                'referer' => 'https://example.com/admin',
                'origin' => 'https://example.com',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame([], $this->decodeResponse($response));
    }

    /**
     * Reject invalid JSON.
     */
    #[Test]
    public function itRejectsInvalidJson(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            null,
            [
                'referer' => 'https://example.com/admin',
            ],
            '{invalid json',
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Invalid payload'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject a payload without links.
     */
    #[Test]
    public function itRejectsPayloadWithoutLinks(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            ['urls' => ['https://example.org']],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Invalid payload'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject a payload where links is not an array.
     */
    #[Test]
    public function itRejectsPayloadWithNonArrayLinks(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            ['links' => 'https://example.org'],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
        );

        self::assertSame(
            ['error' => 'Invalid payload'],
            $this->decodeResponse($response),
        );
    }

    /**
     * Ignore empty and non-string links.
     */
    #[Test]
    public function itIgnoresEmptyAndNonStringLinks(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => [
                    '',
                    '   ',
                    null,
                    123,
                    false,
                    [],
                ],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame([], $this->decodeResponse($response));
    }

    /**
     * Reject more than one hundred links.
     */
    #[Test]
    public function itRejectsMoreThanOneHundredLinks(): void
    {
        $controller = $this->createController();

        $links = array_fill(
            0,
            101,
            self::BASE_URL,
        );

        $request = $this->createRequest(
            ['links' => $links],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode(),
        );

        self::assertSame(
            [
                'error' => 'A maximum of 100 links may be validated at once.',
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Accept exactly one hundred links.
     */
    #[Test]
    public function itAcceptsExactlyOneHundredLinks(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $links = array_fill(
            0,
            100,
            self::BASE_URL,
        );

        $request = $this->createRequest(
            ['links' => $links],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        $results = $this->decodeResponse($response);

        self::assertCount(100, $results);
        self::assertCount(100, $requests);
    }

    /**
     * Resolve relative links.
     */
    #[Test]
    public function itResolvesRelativeLinks(): void
    {
        $requestedUrl = null;

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requestedUrl): MockResponse {
                $requestedUrl = $url;

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => ['/some/page'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame(
            'https://example.com/some/page',
            $requestedUrl,
        );
    }

    /**
     * Resolve relative links without a leading slash.
     */
    #[Test]
    public function itResolvesRelativeLinksWithoutLeadingSlash(): void
    {
        $requestedUrl = null;

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requestedUrl): MockResponse {
                $requestedUrl = $url;

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => ['some/page'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame(
            'https://example.com/some/page',
            $requestedUrl,
        );
    }

    /**
     * Trim links before validation.
     */
    #[Test]
    public function itTrimsLinksBeforeValidation(): void
    {
        $requestedUrl = null;

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requestedUrl): MockResponse {
                $requestedUrl = $url;

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => ['  /some/page  '],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame(
            'https://example.com/some/page',
            $requestedUrl,
        );
    }

    /**
     * Reject an unsupported protocol.
     */
    #[Test]
    public function itRejectsInvalidProtocol(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['ftp://example.org/file.txt'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertSame(
            [
                'url' => 'ftp://example.org/file.txt',
                'ok' => false,
                'status' => null,
                'error' => 'Invalid protocol',
            ],
            $results[0],
        );
    }

    /**
     * Reject malformed URLs.
     */
    #[Test]
    public function itRejectsInvalidUrls(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['://not-a-valid-url'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertFalse($results[0]['ok']);
        self::assertSame('Invalid URL', $results[0]['error']);
    }

    /**
     * Reject private IPv4 addresses.
     */
    #[Test]
    public function itRejectsPrivateIpv4Addresses(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['https://192.168.1.1'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            [
                [
                    'url' => 'https://192.168.1.1',
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ],
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject loopback IPv4 addresses.
     */
    #[Test]
    public function itRejectsLoopbackIpv4Addresses(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['https://127.0.0.1'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            [
                [
                    'url' => 'https://127.0.0.1',
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ],
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject link-local IPv4 addresses.
     */
    #[Test]
    public function itRejectsLinkLocalIpv4Addresses(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['https://169.254.1.1'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            [
                [
                    'url' => 'https://169.254.1.1',
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ],
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject loopback IPv6 addresses.
     */
    #[Test]
    public function itRejectsLoopbackIpv6Addresses(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['https://[::1]'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            [
                [
                    'url' => 'https://[::1]',
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ],
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Reject reserved IPv6 addresses.
     */
    #[Test]
    public function itRejectsReservedIpv6Addresses(): void
    {
        $controller = $this->createController();

        $request = $this->createRequest(
            [
                'links' => ['https://[::]'],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            [
                [
                    'url' => 'https://[::]',
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ],
            ],
            $this->decodeResponse($response),
        );
    }

    /**
     * Use HEAD for successful requests.
     */
    #[Test]
    public function itUsesHeadForSuccessfulRequests(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame(
            [
                ['HEAD', self::BASE_URL . '/'],
            ],
            $requests,
        );
    }

    /**
     * Fall back to GET when HEAD fails.
     */
    #[Test]
    public function itFallsBackToGetWhenHeadFails(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            function (
                string $method,
                string $url,
            ) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                if ('HEAD' === $method) {
                    return new MockResponse('', [
                        'http_code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    ]);
                }

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        self::assertSame(
            [
                ['HEAD', self::BASE_URL . '/'],
                ['GET', self::BASE_URL . '/'],
            ],
            $requests,
        );
    }

    /**
     * Report failed HTTP responses.
     */
    #[Test]
    public function itReportsFailedHttpResponses(): void
    {
        $client = new MockHttpClient(
            static function (
                string $method,
                string $url,
            ): MockResponse {
                return new MockResponse('', [
                    'http_code' => Response::HTTP_NOT_FOUND,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertFalse($results[0]['ok']);
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $results[0]['status'],
        );
    }

    /**
     * Return response headers.
     */
    #[Test]
    public function itReturnsResponseHeaders(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
                'response_headers' => [
                    'Content-Type: text/html',
                    'X-Test: value',
                ],
            ]),
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertSame(
            [
                'content-type' => 'text/html',
                'x-test' => 'value',
            ],
            $results[0]['headers'],
        );
    }

    /**
     * Normalize header names to lowercase.
     */
    #[Test]
    public function itNormalizesHeaderNamesToLowercase(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
                'response_headers' => [
                    'Content-Type: text/html',
                    'X-Custom-Header: test',
                ],
            ]),
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertArrayHasKey(
            'content-type',
            $results[0]['headers'],
        );

        self::assertArrayHasKey(
            'x-custom-header',
            $results[0]['headers'],
        );

        self::assertArrayNotHasKey(
            'Content-Type',
            $results[0]['headers'],
        );

        self::assertArrayNotHasKey(
            'X-Custom-Header',
            $results[0]['headers'],
        );
    }

    /**
     * Return transport errors as failed results.
     */
    #[Test]
    public function itReturnsTransportErrorsAsFailedResults(): void
    {
        $client = new MockHttpClient(
            static function (): never {
                throw new TransportException('Connection failed');
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertFalse($results[0]['ok']);
        self::assertNull($results[0]['status']);
        self::assertSame(
            'Connection failed',
            $results[0]['error'],
        );
    }

    /**
     * Include timing information for successful requests.
     */
    #[Test]
    public function itIncludesTimingInformationForSuccessfulRequests(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
            ]),
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertArrayHasKey('time_ms', $results[0]);
        self::assertIsInt($results[0]['time_ms']);
        self::assertGreaterThanOrEqual(0, $results[0]['time_ms']);
    }

    /**
     * Report zero redirects when there are none.
     */
    #[Test]
    public function itReportsZeroRedirectsWhenThereAreNone(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
                'info' => [
                    'redirect_count' => 0,
                ],
            ]),
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [self::BASE_URL],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertSame(0, $results[0]['redirects']);
    }

    /**
     * Preserve the original absolute URL.
     */
    #[Test]
    public function itPreservesTheOriginalAbsoluteUrl(): void
    {
        $url = 'https://93.184.216.34/some/path?foo=bar';

        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
            ]),
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [$url],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertSame($url, $results[0]['url']);
    }

    /**
     * Process multiple links independently.
     */
    #[Test]
    public function itProcessesMultipleLinksIndependently(): void
    {
        $client = new MockHttpClient(
            static function (
                string $method,
                string $url,
            ): MockResponse {
                if (str_contains($url, '/missing')) {
                    return new MockResponse('', [
                        'http_code' => Response::HTTP_NOT_FOUND,
                    ]);
                }

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = $this->createController($client);

        $request = $this->createRequest(
            [
                'links' => [
                    self::BASE_URL . '/ok',
                    self::BASE_URL . '/missing',
                ],
            ],
            [
                'referer' => 'https://example.com/admin',
            ],
        );

        $response = $controller($request);

        $results = $this->decodeResponse($response);

        self::assertCount(2, $results);

        self::assertTrue($results[0]['ok']);
        self::assertSame(
            Response::HTTP_OK,
            $results[0]['status'],
        );

        self::assertFalse($results[1]['ok']);
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $results[1]['status'],
        );
    }

    /**
     * Create the controller under test.
     */
    private function createController(
        ?HttpClientInterface $httpClient = null,
    ): LinkValidationController {
        return new LinkValidationController(
            $httpClient ?? new MockHttpClient(
                new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]),
            ),
        );
    }

    /**
     * Create a request for the controller.
     *
     * @param array<string, mixed>|null $payload
     * @param array<string, string>     $headers
     */
    private function createRequest(
        ?array $payload,
        array $headers = [],
        ?string $rawContent = null,
    ): Request {
        $request = Request::create(
            '/incp/api/validate-links',
            Request::METHOD_POST,
            [],
            [],
            [],
            [
                'HTTP_HOST' => self::HOST,
                'HTTPS' => 'on',
            ],
            $rawContent ?? json_encode(
                $payload,
                JSON_THROW_ON_ERROR,
            ),
        );

        if (isset($headers['referer'])) {
            $request->headers->set(
                'referer',
                $headers['referer'],
            );
        }

        if (isset($headers['origin'])) {
            $request->headers->set(
                'origin',
                $headers['origin'],
            );
        }

        return $request;
    }

    /**
     * Decode a JSON response.
     *
     * @return array<mixed>
     */
    private function decodeResponse(JsonResponse $response): array
    {
        $content = $response->getContent();

        self::assertIsString($content);

        /** @var array<mixed> $decoded */
        $decoded = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return $decoded;
    }
}
