<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Inachis\Entity\System\CspReport;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CspHeaderManager
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        #[Target('cspCache')]
        private readonly CacheInterface $cspCache,
    ) {
    }

    /**
     * Get the compiled CSP string for the front-end.
     *
     * @return array{name: string, value: string}|null
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
            $cleanedSources = [];
            foreach ($sources as $source) {
                if (!is_string($source)) {
                    continue;
                }
                $source = trim($source);
                if (in_array($source, ['self', 'none', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic'], true)) {
                    $cleanedSources[] = "'$source'";
                } elseif ('data-uri' === $source || 'data' === $source) {
                    $cleanedSources[] = 'data:';
                } else {
                    $cleanedSources[] = $source;
                }
            }

            $directives[] = $directive.' '.implode(' ', $cleanedSources);
        }

        $directives[] = 'report-uri /api/csp/report';

        return implode('; ', $directives).';';
    }

    /**
     * Adds a reported item to the existing CSP policy.
     */
    public function addReportToPolicy(CspReport $report): void
    {
        // 1. Establish secure default baseline template for a brand-new policy
        $defaultPolicyJson = json_encode([
            'default-src' => ['self'],
            'script-src' => ['self'],
            'style-src' => ['self'],
            'img-src' => ['self', 'data-uri'],
        ]) ?: '{}';

        $policySetting = $this->settingRepository->getOrCreateSetting(
            'csp_policy_frontend',
            $defaultPolicyJson,
        );

        $rawPolicy = $policySetting->getValue();
        $policyData = is_string($rawPolicy) ? json_decode($rawPolicy, true) : [];
        if (!is_array($policyData)) {
            $policyData = [];
        }

        /** @var array<string, list<string>> $policy */
        $policy = $policyData;

        // 4. Normalize the directive from the incoming report
        $rawDirective = $report->getEffectiveDirective();
        if (!is_string($rawDirective) || '' === $rawDirective) {
            return;
        }
        $directive = str_replace(['-elem', '-attr'], '', $rawDirective);

        // 5. Extract and clean the blocked URI scheme/host
        $blockedUri = $report->getBlockedUri();
        if (empty($blockedUri)) {
            return;
        }

        $cleanSource = $blockedUri;
        if (in_array(strtolower($cleanSource), ['inline', 'unsafe-inline'], true)) {
            $cleanSource = 'unsafe-inline';
        } elseif (in_array(strtolower($cleanSource), ['eval', 'unsafe-eval'], true)) {
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
        if (!in_array($cleanSource, $policy[$directive], true)) {
            $policy[$directive][] = $cleanSource;
        }

        $encodedPolicy = json_encode($policy);
        $policySetting->setValue(false !== $encodedPolicy ? $encodedPolicy : null);
    }
}
