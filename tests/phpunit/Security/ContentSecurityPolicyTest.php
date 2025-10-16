<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Security;

use App\Exception\InvalidContentSecurityPolicyException;
use App\Security\ContentSecurityPolicy;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @Entity
 * @group unit
 */
class ContentSecurityPolicyTest extends TestCase
{
    /**
     * @var string[] Policies to use for CSP header tests
     */
    protected $csp;

    /**
     * Set-up CSP defaults
     */
    public function setUp(): void
    {
        $this->csp = json_decode(
            '{
                "enforce": {
                    "default-src": {
                        "self": true
                    },
                    "script-src": {
                        "unsafe-eval": true,
                        "self": true,
                        "sources": [
                            "analytics.google.com"
                        ]
                    },
                    "upgrade-insecure-requests": true
                },
                "report": {
                    "style-src": {
                        "self": true,
                        "data": true
                    }
                }
            }',
            true
        );

        parent::setUp();
    }
    /**
     * Test the enforce header
     * @throws InvalidContentSecurityPolicyException
     */
    public function testGenerateCSPEnforceHeader(): void
    {
        $this->assertEquals(
            'default-src \'self\'; script-src \'unsafe-eval\' \'self\' analytics.google.com; upgrade-insecure-requests',
            ContentSecurityPolicy::getCSPEnforceHeader($this->csp)
        );
    }
    /**
     * Test the report header
     * @throws InvalidContentSecurityPolicyException
     */
    public function testGenerateCSPReportHeader(): void
    {
        $this->assertEquals(
            'style-src \'self\' data:',
            ContentSecurityPolicy::getCSPReportHeader($this->csp)
        );
    }
    /**
     * Test the enforce header default is not an empty string
     * @throws InvalidContentSecurityPolicyException
     */
    public function testGenerateCSPEnforceHeaderDefault(): void
    {
        $this->assertEmpty(
            ContentSecurityPolicy::getCSPEnforceHeader()
        );
    }
    /**
     * Test the report header default is not an empty string
     * @throws InvalidContentSecurityPolicyException
     */
    public function testGenerateCSPReportHeaderDefault(): void
    {
        $this->assertEmpty(
            ContentSecurityPolicy::getCSPReportHeader()
        );
    }

    public function testGenerateCSPPolicyFail(): void
    {
        try {
            $csp = json_decode(
                '{
                    "foo-src": {
                        "bar": true
                    }
                }',
                true
            );
            ContentSecurityPolicy::generateCSP($csp);
        } catch (Exception $exception) {
            $this->assertStringContainsString('policy is not supported', $exception->getMessage());
        }
    }

    public function testGenerateCSPDirectiveFail(): void
    {
        try {
            $csp = json_decode(
                '{
                    "default-src": {
                        "bar": true
                    }
                }',
                true
            );
            ContentSecurityPolicy::generateCSP($csp);
        } catch (Exception $exception) {
            $this->assertStringContainsString('Could not understand', $exception->getMessage());
        }
    }
}
