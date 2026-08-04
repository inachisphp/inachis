<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Domain;

use Inachis\Model\Domain\DomainDnsReport;
use Inachis\Model\Domain\Severity;
use Inachis\Model\Domain\ValidationIssue;
use PHPUnit\Framework\TestCase;

final class DomainDnsReportTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $issues = [
            new ValidationIssue(
                'SPF',
                'SPF record is missing',
                Severity::Warning,
            ),
        ];

        $report = new DomainDnsReport(
            domain: 'example.com',
            dkimRecords: ['selector1', 'selector2'],
            mxRecords: [
                [
                    'host' => 'mail.example.com',
                    'priority' => 10,
                ],
            ],
            spfRecords: [
                'v=spf1 include:_spf.example.com ~all',
            ],
            dmarcRecords: [
                'v=DMARC1; p=reject',
            ],
            bimiRecord: [
                [
                    'selector' => 'default',
                ],
            ],
            tlsRptRecords: [
                [
                    'value' => 'v=TLSRPTv1; rua=mailto:tls@example.com',
                ],
            ],
            caaRecords: [
                [
                    'tag' => 'issue',
                    'value' => 'letsencrypt.org',
                ],
            ],
            issues: $issues,
        );

        $this->assertSame('example.com', $report->domain);
        $this->assertSame(['selector1', 'selector2'], $report->dkimRecords);
        $this->assertSame(
            [['host' => 'mail.example.com', 'priority' => 10]],
            $report->mxRecords,
        );
        $this->assertSame(
            ['v=spf1 include:_spf.example.com ~all'],
            $report->spfRecords,
        );
        $this->assertSame(
            ['v=DMARC1; p=reject'],
            $report->dmarcRecords,
        );
        $this->assertSame(
            [['selector' => 'default']],
            $report->bimiRecord,
        );
        $this->assertSame(
            [['value' => 'v=TLSRPTv1; rua=mailto:tls@example.com']],
            $report->tlsRptRecords,
        );
        $this->assertSame(
            [['tag' => 'issue', 'value' => 'letsencrypt.org']],
            $report->caaRecords,
        );
        $this->assertSame($issues, $report->issues);
    }

    public function testHasIssuesReturnsFalseWhenThereAreNoIssues(): void
    {
        $report = new DomainDnsReport(
            domain: 'example.com',
            dkimRecords: [],
            mxRecords: [],
            spfRecords: [],
            dmarcRecords: [],
            bimiRecord: [],
            tlsRptRecords: [],
            caaRecords: [],
            issues: [],
        );

        $this->assertFalse($report->hasIssues());
    }

    public function testHasIssuesReturnsFalseWhenOnlyWarningsAndInfoExist(): void
    {
        $report = new DomainDnsReport(
            domain: 'example.com',
            dkimRecords: [],
            mxRecords: [],
            spfRecords: [],
            dmarcRecords: [],
            bimiRecord: [],
            tlsRptRecords: [],
            caaRecords: [],
            issues: [
                new ValidationIssue(
                    'SPF',
                    'Warning',
                    Severity::Warning,
                ),
                new ValidationIssue(
                    'MX',
                    'Information',
                    Severity::Info,
                ),
            ],
        );

        $this->assertFalse($report->hasIssues());
    }

    public function testHasIssuesReturnsTrueWhenAnErrorExists(): void
    {
        $report = new DomainDnsReport(
            domain: 'example.com',
            dkimRecords: [],
            mxRecords: [],
            spfRecords: [],
            dmarcRecords: [],
            bimiRecord: [],
            tlsRptRecords: [],
            caaRecords: [],
            issues: [
                new ValidationIssue(
                    'SPF',
                    'Warning',
                    Severity::Warning,
                ),
                new ValidationIssue(
                    'DMARC',
                    'Configuration error',
                    Severity::Error,
                ),
                new ValidationIssue(
                    'MX',
                    'Information',
                    Severity::Info,
                ),
            ],
        );

        $this->assertTrue($report->hasIssues());
    }
}
