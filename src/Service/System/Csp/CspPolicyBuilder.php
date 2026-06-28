<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Csp;

class CspPolicyBuilder
{
    /**
     * Compiles unique DB entries into a structured associative array or string policy.
     */
    public function buildPolicyFromReports(array $rawReports): array
    {
        $policy = [];

        foreach ($rawReports as $report) {
            $directive = $report['effectiveDirective'];
            $blockedUri = $report['blockedUri'];

            if (empty($directive) || empty($blockedUri)) {
                continue;
            }

            $source = $this->normalizeBlockedUri($blockedUri);
            if ($source !== null) {
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
        if ($uri === 'inline') {
            return "'unsafe-inline'";
        }
        if ($uri === 'eval') {
            return "'unsafe-eval'";
        }
        if (in_array($uri, ['self', 'about', 'blob', 'data', 'mediastream', 'filesystem'])) {
            return "'" . $uri . "'";
        }

        // Extract host or scheme from actual URIs
        $parsed = parse_url($uri);
        if (isset($parsed['scheme']) && isset($parsed['host'])) {
            // Keep the scheme + host (ignore specific file paths/query strings)
            return $parsed['scheme'] . '://' . $parsed['host'];
        }
        
        if (isset($parsed['host'])) {
            return $parsed['host'];
        }

        // Fallback for relative or string junk we couldn't parse safely
        return !empty($uri) && str_contains($uri, '.') ? $uri : null;
    }

    /**
     * Optional utility to format the policy array into a raw HTTP header string.
     */
    public function stringifyPolicy(array $compiledPolicy): string
    {
        $headerParts = [];
        foreach ($compiledPolicy as $directive => $sources) {
            $headerParts[] = $directive . ' ' . implode(' ', $sources);
        }
        return implode('; ', $headerParts);
    }
}
