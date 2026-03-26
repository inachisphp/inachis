<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Link Validation Controller
 */
#[IsGranted('ROLE_ADMIN')]
final class LinkValidationController
{
	private string $baseUrl;

	/**
	 * Constructor
	 *
	 * @param HttpClientInterface $httpClient
	 */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

	/**
	 * Validate links
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
    #[Route('/incc/api/validate-links', name: 'api_validate_links', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
		// Referrer protection
		$referer = $request->headers->get('referer');
		if (!is_string($referer)) {
			return new JsonResponse(['error' => 'Missing referer'], 403);
		}
		$refererHost = parse_url($referer, PHP_URL_HOST);
		$currentHost = $request->getHost();
		if (!is_string($refererHost) || $refererHost !== $currentHost) {
			return new JsonResponse(['error' => 'Invalid referer'], 403);
		}

		// Set base URL in case of relative links
		$this->baseUrl = $request->getSchemeAndHttpHost();

		$origin = $request->headers->get('origin');
        if (is_string($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            if (!is_string($originHost) || $originHost !== $currentHost) {
                return new JsonResponse(['error' => 'Invalid origin'], 403);
            }
        }

        /** @var mixed $decoded */
        $decoded = json_decode($request->getContent(), true);

        if (!is_array($decoded) || !isset($decoded['links']) || !is_array($decoded['links'])) {
            return new JsonResponse(
                ['error' => 'Invalid payload'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        /** @var array<int, mixed> $rawLinks */
        $rawLinks = $decoded['links'];

        $links = array_values(array_filter(
            $rawLinks,
            static fn ($l): bool => is_string($l) && $l !== ''
        ));

        $results = [];

        foreach ($links as $url) {
            $results[] = $this->validateSingleLink($url);
        }

        return new JsonResponse($results);
    }

    /**
	 * Validate a single link
	 *
     * @return array{
     *     url: string,
     *     ok: bool,
     *     status: int|null,
     *     headers?: array<string, string>,
     *     error?: string
     * }
     */
    private function validateSingleLink(string $url): array
    {
		if (!preg_match('#^https?://#i', $url)) {
            if (!str_starts_with($url, '/')) {
                $url = '/' . $url;
            }

            $url = rtrim($this->baseUrl, '/') . $url;
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
		if (!is_string($scheme) || !in_array(strtolower($scheme), ['http', 'https'], true)) {
			return [
				'url' => $url,
				'ok' => false,
				'status' => null,
				'error' => 'Invalid protocol',
			];
		}
		$host = parse_url($url, PHP_URL_HOST);
		if ($host !== $this->baseUrl) {
            // SSRF protection
            $records = dns_get_record($host, DNS_A + DNS_AAAA);

            if ($records === false) {
                return [
                    'url' => $url,
                    'ok' => false,
                    'status' => null,
                    'error' => 'DNS lookup failed',
                ];
            }

            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (!is_string($ip)) {
                    continue;
                }

                if (!filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                )) {
                    return [
                        'url' => $url,
                        'ok' => false,
                        'status' => null,
                        'error' => 'Blocked (private network)',
                    ];
                }
            }
        }
        try {
            $start = microtime(true);
            $response = $this->httpClient->request('HEAD', $url, [
                'timeout' => 5,
                'max_redirects' => 5,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400 || $statusCode === 405) {
                $response = $this->httpClient->request('GET', $url, [
                    'timeout' => 5,
                    'max_redirects' => 5,
                ]);

                $statusCode = $response->getStatusCode();
            }

			$timeMs = (int)((microtime(true) - $start) * 1000);
			$info = $response->getInfo();
			$redirectCount = $info['redirect_count'] ?? 0;

            return [
                'url' => $url,
                'ok' => $statusCode >= 200 && $statusCode < 400,
                'status' => $statusCode,
                'headers' => $this->normalizeHeaders($response->getHeaders(false)),
				'time_ms' => $timeMs,
				'redirects' => $redirectCount,
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
     * Normalize headers
	 *
     * @param array<string, array<int, string>> $headers
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