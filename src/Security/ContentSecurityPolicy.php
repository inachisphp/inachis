<?php

namespace App\Security;

use App\Exception\InvalidContentSecurityPolicyException;

/**
 * Object for managing CSP headers which should be valid
 * https://cspvalidator.org and https://content-security-policy.com/.
 */
final class ContentSecurityPolicy
{
    /**
     * @var string[] SRC-based directives
     */
    public static $srcDirectives = [
        'default-src',
        'child-src',
        'connect-src',
        'font-src',
        'form-action',
        'frame-ancestors',
        'frame-src',
        'img-src',
        'media-src',
        'object-src',
        'plugin-types',
        'script-src',
        'style-src',
        'xhr-src',
    ];
    /**
     * @var string[] URI-based directives for reporting
     */
    public static $uriDirectives = [
        'report-uri',
        'policy-uri',
    ];
    /**
     * @var string[] Directives used for toggling policy components
     */
    public static $otherDirectives = [
        'block-all-mixed-content',
        'sandbox',
        'upgrade-insecure-requests',
    ];
    /**
     * @var ContentSecurityPolicy reference to instance of self
     */
    protected static $instance;

    /**
     * Returns an instance of {@link Application}.
     *
     * @return ContentSecurityPolicy The current or a new instance of {@link Application}
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Returns the CSP policy for enforcing.
     *
     * @param string[] The policy to process for CSP enforce
     *
     * @throws InvalidContentSecurityPolicyException
     *
     * @return string The parsed policies
     */
    public static function getCSPEnforceHeader($policy)
    {
        if (!empty($policy) && !empty($policy['enforce'])) {
            return self::generateCSP($policy['enforce']);
        }
    }

    /**
     * Returns the CSP policy for reporting only.
     *
     * @param string[] The policy to process for CSP reporting
     *
     * @throws InvalidContentSecurityPolicyException
     *
     * @return string The parsed policies
     */
    public static function getCSPReportHeader($policy = [])
    {
        if (!empty($policy) && !empty($policy['report'])) {
            return self::generateCSP($policy['report']);
        }
    }

    /**
     * Generates the CSP from the given policy JSON object.
     *
     * @param mixed[] $csp The CSP policy
     *
     * @throws InvalidContentSecurityPolicyException
     *
     * @return string The parsed policies
     */
    public static function generateCSP($csp)
    {
        $policies = [];
        foreach ($csp as $policy => $directives) {
            if (!in_array($policy, self::$srcDirectives) &&
                !in_array($policy, self::$uriDirectives) &&
                !in_array($policy, self::$otherDirectives)
            ) {
                throw new InvalidContentSecurityPolicyException(
                    sprintf('%s policy is not supported or is invalid', $policy)
                );
            }
            $policies[$policy] = $policy;
            if (!empty($directives) && is_array($directives)) {
                foreach ($directives as $directive => $value) {
                    switch ($directive) {
                        case 'data':
                            if ($value === true) {
                                $policies[$policy] .= ' data:';
                            }
                            break;
                        case 'self':
                        case 'unsafe-inline':
                        case 'unsafe-eval':
                            if ($value === true) {
                                $policies[$policy] .= ' \''.$directive.'\'';
                            }
                            break;

                        case 'sources':
                            if (!empty($value)) {
                                foreach ($value as $source) {
                                    $policies[$policy] .= ' '.$source;
                                }
                            }
                            break;

                        default:
                            throw new InvalidContentSecurityPolicyException(
                                sprintf('Could not understand %s directive', $directive)
                            );
                    }
                }
            }
        }

        return trim(implode('; ', $policies));
    }
}
