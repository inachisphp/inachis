<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Validator;

use PHPUnit\Framework\TestCase;
use Inachis\Validator\TlsRptValidator;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

final class TlsRptValidatorTest extends TestCase
{
    public function testValidateWithNoRecords(): void
    {
        $validator = new TlsRptValidator();

        $issues = $validator->validate([]);

        $this->assertCount(1, $issues);
        $this->assertInstanceOf(ValidationIssue::class, $issues[0]);
        $this->assertSame('tls-rpt', $issues[0]->getType());
        $this->assertStringContainsString(
            'Invalid TLS-RPT record format:',
            $issues[0]->getMessage()
        );
        $this->assertSame(Severity::Error, $issues[0]->getSeverity());
    }

    public function testValidateWithValidRecords(): void
    {
        $validator = new TlsRptValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'v=TLSRPT1; rua=mailto:[EMAIL_ADDRESS]'
            ]
        ];

        $issues = $validator->validate($records);

        $this->assertCount(0, $issues);
    }
}