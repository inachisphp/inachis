<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

class CspPolicyBuilder
{
    /**
     * Compiles unique DB entries into a structured associative array or string policy.
     *
     * @param list<array{effectiveDirective?: string|null, blockedUri?: string|null}> $rawReports
     * @return array<string, list<string>>
     */
    public function buildPolicyFromReports(array $rawReports): array
    {
        /** @var array<string, array<string, true>> $policy */
        $policy = [];

        foreach ($rawReports as $report) {
            $directive = $report['effectiveDirective'] ?? null;
            $blockedUri = $report['blockedUri'] ?? null;

            if (empty($directive) || empty($blockedUri)) {
                continue;
            }

            $source = $this->normalizeBlockedUri($blockedUri);
            if (null !== $source) {
                $policy[$directive][$source] = true;
            }
        }

        // Convert the map keys into index arrays for easy template rendering
        $compiledPolicy = [];
        foreach ($policy as $directive => $sources) {
            $compiledPolicy[$directive] = array_keys($sources);
        }

        return $compiledPolicy;
    }

    /**
     * Cleans up blocked URIs into standard CSP sources.
     */
    private function normalizeBlockedUri(string $uri): ?string
    {
        // Handle keywords
        if ('inline' === $uri) {
            return "'unsafe-inline'";
        }
        if ('eval' === $uri) {
            return "'unsafe-eval'";
        }
        if (in_array($uri, ['self', 'about', 'blob', 'data', 'mediastream', 'filesystem'], true)) {
            return "'".$uri."'";
        }

        // Extract host or scheme from actual URIs
        $parsed = parse_url($uri);
        if (isset($parsed['scheme']) && isset($parsed['host'])) {
            // Keep the scheme + host (ignore specific file paths/query strings)
            return $parsed['scheme'].'://'.$parsed['host'];
        }

        if (isset($parsed['host'])) {
            return $parsed['host'];
        }

        // Fallback for relative or string rubbish we couldn't parse safely
        return !empty($uri) && str_contains($uri, '.') ? $uri : null;
    }

    /**
     * Format the policy array into a raw HTTP header string.
     *
     * @param array<string, list<string>> $compiledPolicy
     */
    public function stringifyPolicy(array $compiledPolicy): string
    {
        $headerParts = [];
        foreach ($compiledPolicy as $directive => $sources) {
            $headerParts[] = $directive.' '.implode(' ', $sources);
        }

        return implode('; ', $headerParts);
    }
}
