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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LinkValidationControllerTest extends TestCase
{
    #[Test]
    public function itRejectsRequestWithoutReferer(): void
    {
        $client = new MockHttpClient();
        $controller = new LinkValidationController($client);

        $request = Request::create(
            '/incp/api/validate-links',
            'POST',
            server: [
                'HTTP_HOST' => 'example.com',
            ],
            content: json_encode(['links' => ['https://example.org']]),
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid request origin'],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itRejectsRequestWithInvalidReferer(): void
    {
        $client = new MockHttpClient();
        $controller = new LinkValidationController($client);

        $request = $this->createRequest(
            ['https://example.org'],
            [
                'HTTP_REFERER' => 'https://evil.example/',
            ],
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid request origin'],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itRejectsRequestWithInvalidOrigin(): void
    {
        $client = new MockHttpClient();
        $controller = new LinkValidationController($client);

        $request = $this->createRequest(
            ['https://example.org'],
            [
                'HTTP_ORIGIN' => 'https://evil.example',
            ],
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid request origin'],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itAcceptsRequestWithMatchingRefererAndOrigin(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_OK,
            ]),
        );

        $controller = new LinkValidationController($client);

        $request = $this->createRequest(
            ['https://example.org'],
            [
                'HTTP_ORIGIN' => 'https://example.com',
            ],
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertIsArray($results);
        self::assertCount(1, $results);
        self::assertTrue($results[0]['ok']);
    }

    #[Test]
    public function itRejectsInvalidJson(): void
    {
        $client = new MockHttpClient();
        $controller = new LinkValidationController($client);

        $request = Request::create(
            '/incp/api/validate-links',
            'POST',
            server: [
                'HTTP_HOST' => 'example.com',
                'HTTP_REFERER' => 'https://example.com/editor',
            ],
            content: '{invalid json',
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid payload'],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itRejectsPayloadWithoutLinks(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        $request = $this->createRequest([]);

        $request->setContent(
            json_encode(['urls' => ['https://example.org']]),
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Invalid payload'],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itIgnoresEmptyAndNonStringLinks(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $request = $this->createRequest([
            '',
            '   ',
            null,
            123,
            false,
            'https://example.org',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertIsArray($results);
        self::assertCount(1, $results);
        self::assertSame(
            'https://example.org',
            $results[0]['url'],
        );

        self::assertCount(1, $requests);
    }

    #[Test]
    public function itRejectsMoreThanOneHundredLinks(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        $request = $this->createRequest(
            array_fill(0, 101, 'https://example.org'),
        );

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        self::assertSame(
            [
                'error' => 'A maximum of 100 links may be validated at once.',
            ],
            json_decode($response->getContent(), true),
        );
    }

    #[Test]
    public function itResolvesRelativeLinks(): void
    {
        $requestedUrl = null;

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requestedUrl): MockResponse {
                $requestedUrl = $url;

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $request = $this->createRequest([
            '/some/page',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('https://example.com/some/page', $requestedUrl);
    }

    #[Test]
    public function itResolvesRelativeLinksWithoutLeadingSlash(): void
    {
        $requestedUrl = null;

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requestedUrl): MockResponse {
                $requestedUrl = $url;

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $request = $this->createRequest([
            'some/page',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('https://example.com/some/page', $requestedUrl);
    }

    #[Test]
    public function itRejectsInvalidProtocol(): void
    {
        $client = new MockHttpClient();
        $controller = new LinkValidationController($client);

        $request = $this->createRequest([
            'ftp://example.org/file.txt',
        ]);

        $response = $controller($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertSame(
            [
                'url' => 'ftp://example.org/file.txt',
                'ok' => false,
                'status' => null,
                'error' => 'Invalid URL',
            ],
            $results[0],
        );
    }

    #[Test]
    public function itRejectsPrivateIpv4Addresses(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        foreach ([
            '10.0.0.1',
            '172.16.0.1',
            '192.168.1.1',
        ] as $ip) {
            $request = $this->createRequest([
                'http://'.$ip.'/',
            ]);

            $response = $controller($request);

            $results = json_decode($response->getContent(), true);

            self::assertSame(
                'Blocked (private or reserved network)',
                $results[0]['error'],
                $ip,
            );
        }
    }

    #[Test]
    public function itRejectsLoopbackIpv4Addresses(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        $request = $this->createRequest([
            'http://127.0.0.1/',
        ]);

        $response = $controller($request);

        $results = json_decode($response->getContent(), true);

        self::assertSame(
            'Blocked (private or reserved network)',
            $results[0]['error'],
        );
    }

    #[Test]
    public function itRejectsLinkLocalIpv4Addresses(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        $request = $this->createRequest([
            'http://169.254.1.1/',
        ]);

        $response = $controller($request);

        $results = json_decode($response->getContent(), true);

        self::assertSame(
            'Blocked (private or reserved network)',
            $results[0]['error'],
        );
    }

    #[Test]
    public function itRejectsLoopbackIpv6Addresses(): void
    {
        $controller = new LinkValidationController(
            new MockHttpClient(),
        );

        $request = $this->createRequest([
            'http://[::1]/',
        ]);

        $response = $controller($request);

        $results = json_decode($response->getContent(), true);

        self::assertSame(
            'Blocked (private or reserved network)',
            $results[0]['error'],
        );
    }

    #[Test]
    public function itUsesHeadForSuccessfulRequests(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest(['https://example.org']),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertSame(
            [
                ['HEAD', 'https://example.org'],
            ],
            $requests,
        );
    }

    #[Test]
    public function itFallsBackToGetWhenHeadFails(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                return new MockResponse('', [
                    'http_code' => 'HEAD' === $method
                        ? Response::HTTP_METHOD_NOT_ALLOWED
                        : Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest(['https://example.org']),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertSame(
            [
                ['HEAD', 'https://example.org'],
                ['GET', 'https://example.org'],
            ],
            $requests,
        );
    }

    #[Test]
    public function itReportsFailedHttpResponses(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_NOT_FOUND,
            ]),
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest(['https://example.org/missing']),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertFalse($results[0]['ok']);
        self::assertSame(Response::HTTP_NOT_FOUND, $results[0]['status']);
    }

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

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest(['https://example.org']),
        );

        $results = json_decode($response->getContent(), true);

        self::assertSame(
            'text/html',
            $results[0]['headers']['content-type'],
        );

        self::assertSame(
            'value',
            $results[0]['headers']['x-test'],
        );
    }

    #[Test]
    public function itReturnsTransportErrorsAsFailedResults(): void
    {
        $client = new MockHttpClient(
            static function (): never {
                throw new TransportException('Connection failed');
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest(['https://example.org']),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertFalse($results[0]['ok']);
        self::assertNull($results[0]['status']);
        self::assertSame('Connection failed', $results[0]['error']);
    }

    #[Test]
    public function itFollowsPublicRedirects(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                if ('https://example.org/redirect' === $url) {
                    return new MockResponse('', [
                        'http_code' => Response::HTTP_FOUND,
                        'response_headers' => [
                            'Location: https://example.org/final',
                        ],
                    ]);
                }

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest([
                'https://example.org/redirect',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertTrue($results[0]['ok']);
        self::assertSame(
            'https://example.org/final',
            $results[0]['url'],
        );
        self::assertSame(1, $results[0]['redirects']);

        self::assertCount(2, $requests);
    }

    #[Test]
    public function itBlocksRedirectsToPrivateAddresses(): void
    {
        $client = new MockHttpClient(
            new MockResponse('', [
                'http_code' => Response::HTTP_FOUND,
                'response_headers' => [
                    'Location: http://127.0.0.1/admin',
                ],
            ]),
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest([
                'https://example.org/redirect',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertFalse($results[0]['ok']);
        self::assertSame(
            'Blocked (private or reserved network)',
            $results[0]['error'],
        );
        self::assertSame(1, $results[0]['redirects']);
    }

    #[Test]
    public function itStopsFollowingRedirectsAfterMaximum(): void
    {
        $requests = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requests): MockResponse {
                $requests[] = [$method, $url];

                $number = (int) substr(
                    (string) parse_url($url, PHP_URL_PATH),
                    1,
                );

                return new MockResponse('', [
                    'http_code' => Response::HTTP_FOUND,
                    'response_headers' => [
                        'Location: https://example.org/'.($number + 1),
                    ],
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest([
                'https://example.org/0',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $results = json_decode($response->getContent(), true);

        self::assertFalse($results[0]['ok']);
        self::assertSame(5, $results[0]['redirects']);
        self::assertSame(Response::HTTP_FOUND, $results[0]['status']);

        self::assertCount(6, $requests);
    }

    #[Test]
    public function itResolvesRelativeRedirects(): void
    {
        $requestedUrls = [];

        $client = new MockHttpClient(
            static function (string $method, string $url) use (&$requestedUrls): MockResponse {
                $requestedUrls[] = $url;

                if ('https://example.org/path/start' === $url) {
                    return new MockResponse('', [
                        'http_code' => Response::HTTP_FOUND,
                        'response_headers' => [
                            'Location: next',
                        ],
                    ]);
                }

                return new MockResponse('', [
                    'http_code' => Response::HTTP_OK,
                ]);
            },
        );

        $controller = new LinkValidationController($client);

        $response = $controller(
            $this->createRequest([
                'https://example.org/path/start',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertSame(
            [
                'https://example.org/path/start',
                'https://example.org/path/next',
            ],
            $requestedUrls,
        );
    }

    /**
     * Create a valid API request.
     *
     * @param array<int, mixed> $links
     * @param array<string, string> $server
     */
    private function createRequest(
        array $links,
        array $server = [],
    ): Request {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_REFERER' => 'https://example.com/editor',
            ...$server,
        ];

        return Request::create(
            '/incp/api/validate-links',
            'POST',
            server: $server,
            content: json_encode(
                ['links' => $links],
                JSON_THROW_ON_ERROR,
            ),
        );
    }
}
