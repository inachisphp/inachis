<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Url;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Link Validation Controller.
 */
final class LinkValidationController
{
    private const MAX_LINKS = 100;

    private const REQUEST_TIMEOUT = 5.0;

    private const MAX_REDIRECTS = 5;

    private string $baseUrl = '';

    /**
     * Constructor.
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Validate links.
     */
    #[Route(
        '/incp/api/validate-links',
        name: 'api_validate_links',
        methods: ['POST'],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $currentHost = $request->getHost();

        // Referrer protection.
        $referer = $request->headers->get('referer');

        if (!is_string($referer)) {
            return new JsonResponse(
                ['error' => 'Missing referer'],
                JsonResponse::HTTP_FORBIDDEN,
            );
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);

        if (!is_string($refererHost) || !hash_equals($currentHost, $refererHost)) {
            return new JsonResponse(
                ['error' => 'Invalid referer'],
                JsonResponse::HTTP_FORBIDDEN,
            );
        }

        // Set the base URL for relative links.
        $this->baseUrl = $request->getSchemeAndHttpHost();

        // Validate the Origin header when supplied.
        $origin = $request->headers->get('origin');

        if (is_string($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);

            if (!is_string($originHost) || !hash_equals($currentHost, $originHost)) {
                return new JsonResponse(
                    ['error' => 'Invalid origin'],
                    JsonResponse::HTTP_FORBIDDEN,
                );
            }
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode(
                $request->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException) {
            return new JsonResponse(
                ['error' => 'Invalid payload'],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        if (
            !is_array($decoded)
            || !isset($decoded['links'])
            || !is_array($decoded['links'])
        ) {
            return new JsonResponse(
                ['error' => 'Invalid payload'],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        /** @var array<int, mixed> $rawLinks */
        $rawLinks = $decoded['links'];

        $links = array_values(array_filter(
            $rawLinks,
            static fn (mixed $link): bool => is_string($link) && '' !== trim($link),
        ));

        if (count($links) > self::MAX_LINKS) {
            return new JsonResponse(
                [
                    'error' => sprintf(
                        'A maximum of %d links may be validated at once.',
                        self::MAX_LINKS,
                    ),
                ],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        $results = [];

        foreach ($links as $url) {
            $results[] = $this->validateSingleLink($url);
        }

        return new JsonResponse($results);
    }

    /**
     * Validate a single link.
     *
     * @return array{
     *     url: string,
     *     ok: bool,
     *     status: int|null,
     *     headers?: array<string, string>,
     *     error?: string,
     *     time_ms?: int,
     *     redirects?: int
     * }
     */
    private function validateSingleLink(string $url): array
    {
        $url = trim($url);

        /*
         * Determine whether this is an absolute URL before attempting to
         * resolve it as a relative URL. This is important because an URL
         * such as ftp://example.org must not become
         * https://our-site/ftp://example.org.
         */
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (null !== $scheme) {
            if (!is_string($scheme) || !in_array(
                strtolower($scheme),
                ['http', 'https'],
                true,
            )) {
                return [
                    'url' => $url,
                    'ok' => false,
                    'status' => null,
                    'error' => 'Invalid protocol',
                ];
            }
        } else {
            if (!str_starts_with($url, '/')) {
                $url = '/'.$url;
            }

            $url = rtrim($this->baseUrl, '/').$url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'url' => $url,
                'ok' => false,
                'status' => null,
                'error' => 'Invalid URL',
            ];
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (
            !is_string($scheme)
            || !in_array(strtolower($scheme), ['http', 'https'], true)
        ) {
            return [
                'url' => $url,
                'ok' => false,
                'status' => null,
                'error' => 'Invalid protocol',
            ];
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || '' === $host) {
            return [
                'url' => $url,
                'ok' => false,
                'status' => null,
                'error' => 'Invalid URL',
            ];
        }

        /*
         * Protect against SSRF. Literal IP addresses can be checked
         * directly and do not require DNS resolution.
         */
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!$this->isPublicIp($host)) {
                return [
                    'url' => $url,
                    'ok' => false,
                    'status' => null,
                    'error' => 'Blocked (private or reserved network)',
                ];
            }
        } else {
            /*
             * Resolve hostnames before making the request so that private
             * and reserved addresses cannot be reached through DNS.
             */
            $records = dns_get_record(
                $host,
                DNS_A | DNS_AAAA,
            );

            if (false === $records || [] === $records) {
                return [
                    'url' => $url,
                    'ok' => false,
                    'status' => null,
                    'error' => 'DNS lookup failed',
                ];
            }

            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (!is_string($ip) || !$this->isPublicIp($ip)) {
                    return [
                        'url' => $url,
                        'ok' => false,
                        'status' => null,
                        'error' => 'Blocked (private or reserved network)',
                    ];
                }
            }
        }

        try {
            $start = microtime(true);

            $response = $this->httpClient->request(
                'HEAD',
                $url,
                [
                    'timeout' => self::REQUEST_TIMEOUT,
                    'max_redirects' => self::MAX_REDIRECTS,
                ],
            );

            $statusCode = $response->getStatusCode();

            /*
             * Some servers do not implement HEAD correctly. Retry with GET
             * when HEAD returns an HTTP error.
             */
            if ($statusCode >= 400) {
                $response = $this->httpClient->request(
                    'GET',
                    $url,
                    [
                        'timeout' => self::REQUEST_TIMEOUT,
                        'max_redirects' => self::MAX_REDIRECTS,
                    ],
                );

                $statusCode = $response->getStatusCode();
            }

            $timeMs = (int) ((microtime(true) - $start) * 1000);

            /** @var array{redirect_count?: int} $info */
            $info = $response->getInfo();

            return [
                'url' => $url,
                'ok' => $statusCode >= 200 && $statusCode < 400,
                'status' => $statusCode,
                'headers' => $this->normalizeHeaders(
                    $response->getHeaders(false),
                ),
                'time_ms' => $timeMs,
                'redirects' => $info['redirect_count'] ?? 0,
            ];
        } catch (RedirectionExceptionInterface $e) {
            $info = $e->getResponse()->getInfo();

            return [
                'url' => $url,
                'ok' => false,
                'status' => $e->getResponse()->getStatusCode(),
                'error' => 'Maximum redirects exceeded',
                'redirects' => $info['redirect_count'] ?? self::MAX_REDIRECTS,
            ];
        } catch (ExceptionInterface $e) {
            return [
                'url' => $url,
                'ok' => false,
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine whether an IP address is publicly routable.
     */
    private function isPublicIp(string $ip): bool
    {
        return false !== filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }

    /**
     * Normalize response headers.
     *
     * @param array<string, array<int, string>> $headers
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $values) {
            if (isset($values[0])) {
                $normalized[strtolower($key)] = $values[0];
            }
        }

        return $normalized;
    }
}
