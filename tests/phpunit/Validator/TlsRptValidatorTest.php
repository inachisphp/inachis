<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Validator;

use Inachis\Model\Domain\Severity;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Validator\TlsRptValidator;
use PHPUnit\Framework\TestCase;

final class TlsRptValidatorTest extends TestCase
{
    public function testValidateWithNoRecords(): void
    {
        $validator = new TlsRptValidator();

        $issues = $validator->validate([]);

        $this->assertCount(0, $issues);
    }

    public function testValidateWithInvalidateRecord(): void
    {
        $validator = new TlsRptValidator();
        $issues = $validator->validate([
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'invalid record',
            ],
        ]);

        $this->assertCount(1, $issues);
        $this->assertInstanceOf(ValidationIssue::class, $issues[0]);
        $this->assertSame('tls-rpt', $issues[0]->type);
        $this->assertStringContainsString(
            'Invalid TLS-RPT record format:',
            $issues[0]->message,
        );
        $this->assertSame(Severity::Error, $issues[0]->severity);
    }

    public function testValidateWithValidRecords(): void
    {
        $validator = new TlsRptValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'v=TLSRPTv1; rua=mailto:[EMAIL_ADDRESS]',
            ],
        ];

        $issues = $validator->validate($records);

        $this->assertCount(0, $issues);
    }
}
