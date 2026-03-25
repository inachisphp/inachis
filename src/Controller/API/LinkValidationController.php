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
final class LinkValidationController
{
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

        $links = array_values(array_filter($rawLinks, static fn ($l): bool => is_string($l)));

        $results = [];

        foreach ($links as $url) {
            $results[] = $this->validateSingleLink($url);
        }

        return new JsonResponse($results);
    }

    /**
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
		if (!is_string($host) || $host === '') {
			return [
				'url' => $url,
				'ok' => false,
				'status' => null,
				'error' => 'Invalid host',
			];
		}
		// SSRF protection
		$records = dns_get_record($host, DNS_A + DNS_AAAA);
		foreach ($records as $record) {
			$ip = $record['ip'] ?? $record['ipv6'] ?? null;

			if ($ip === null) {
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