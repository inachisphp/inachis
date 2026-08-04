<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Inachis\Entity\System\CspReport;
use Inachis\Repository\System\SettingRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CspHeaderManager
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly CacheInterface $cspCache,
    ) {
    }

    /**
     * Get the compiled CSP string for the front-end.
     *
     * @return array{}
     */
    public function getFrontendHeaderConfig(): ?array
    {
        return $this->cspCache->get('csp_frontend_config', function (ItemInterface $item) {
            $item->expiresAfter(null);

            $modeSetting = $this->settingRepository->findOneBy(['name' => 'csp_mode']);
            $mode = $modeSetting ? $modeSetting->getValue() : 'off';
            if ('off' === $mode) {
                return null;
            }

            $headerName = ('report-only' === $mode)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $policySetting = $this->settingRepository->findOneBy(['name' => 'csp_policy_frontend']);
            $upgradeSetting = $this->settingRepository->findOneBy(['name' => 'csp_upgrade_insecure']);

            $upgradeInsecure = ($upgradeSetting && '1' === $upgradeSetting->getValue());
            $headerValue = ($policySetting && $policySetting->getValue())
                ? $this->compileJsonToCspString($policySetting->getValue(), $upgradeInsecure)
                : "default-src 'self'; report-uri /api/csp/report;";

            return [
                'name' => $headerName,
                'value' => $headerValue,
            ];
        });
    }

    /**
     * Clear the cache when an admin changes settings.
     */
    public function invalidateCache(): void
    {
        $this->cspCache->delete('csp_frontend_config');
    }

    /**
     * Compiles a JSON policy schema into a raw CSP header value.
     *
     * @param string $jsonConfig      The JSON string to convert into a header
     * @param bool   $upgradeInsecure Whether or not HTTP requests should be upgraded to HTTPS automatically
     *
     * @return string The compiled CSP header value
     */
    private function compileJsonToCspString(string $jsonConfig, bool $upgradeInsecure = false): string
    {
        $config = json_decode($jsonConfig, true);
        if (!is_array($config)) {
            return "default-src 'self'; report-uri /api/csp/report;";
        }

        $directives = [];
        if ($upgradeInsecure) {
            $directives[] = 'upgrade-insecure-requests';
        }

        foreach ($config as $directive => $sources) {
            if (empty($sources) || !is_array($sources)) {
                continue;
            }

            // Map keywords to wrapped versions (e.g., self -> 'self', data -> data:)
            $cleanedSources = array_map(function ($source) {
                $source = trim($source);
                if (in_array($source, ['self', 'none', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic'])) {
                    return "'$source'";
                }
                if ('data-uri' === $source || 'data' === $source) {
                    return 'data:';
                }

                return $source;
            }, $sources);

            $directives[] = $directive.' '.implode(' ', $cleanedSources);
        }

        $directives[] = 'report-uri /api/csp/report';

        return implode('; ', $directives).';';
    }

    /**
     * Adds a reported item to the existing CSP policy.
     *
     * @param CspReport $report
     */
    public function addReportToPolicy($report): void
    {
        // 1. Establish secure default baseline template for a brand-new policy
        $defaultPolicyJson = json_encode([
            'default-src' => ['self'],
            'script-src' => ['self'],
            'style-src' => ['self'],
            'img-src' => ['self', 'data-uri'],
        ]);

        $policySetting = $this->settingRepository->getOrCreateSetting(
            'csp_policy_frontend',
            $defaultPolicyJson,
        );
        $policy = json_decode($policySetting->getValue(), true) ?? [];

        // 4. Normalize the directive from the incoming report
        $rawDirective = $report->getEffectiveDirective();
        if (empty($rawDirective)) {
            return;
        }
        $directive = str_replace(['-elem', '-attr'], '', $rawDirective);

        // 5. Extract and clean the blocked URI scheme/host
        $blockedUri = $report->getBlockedUri();
        if (empty($blockedUri)) {
            return;
        }

        $cleanSource = $blockedUri;
        if (in_array(strtolower($cleanSource), ['inline', 'unsafe-inline'])) {
            $cleanSource = 'unsafe-inline';
        } elseif (in_array(strtolower($cleanSource), ['eval', 'unsafe-eval'])) {
            $cleanSource = 'unsafe-eval';
        } elseif (filter_var($blockedUri, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($blockedUri);
            $cleanSource = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
        }

        // Initialize the specific directive array block if missing
        if (!isset($policy[$directive])) {
            $policy[$directive] = ['self'];
        }

        // 6. Append the domain if it's unique, serialize, and save
        if (!in_array($cleanSource, $policy[$directive])) {
            $policy[$directive][] = $cleanSource;
        }

        $policySetting->setValue(json_encode($policy));
    }
}
