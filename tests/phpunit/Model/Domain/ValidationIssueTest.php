<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Domain;

use Inachis\Model\Domain\Severity;
use Inachis\Model\Domain\ValidationIssue;
use PHPUnit\Framework\TestCase;

final class ValidationIssueTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $issue = new ValidationIssue(
            'SPF',
            'SPF record is missing',
            Severity::Warning,
        );

        $this->assertSame('SPF', $issue->type);
        $this->assertSame('SPF record is missing', $issue->message);
        $this->assertSame(Severity::Warning, $issue->severity);
    }

    public function testCanRepresentErrorSeverity(): void
    {
        $issue = new ValidationIssue(
            'DMARC',
            'DMARC policy missing',
            Severity::Error,
        );

        $this->assertSame(Severity::Error, $issue->severity);
    }

    public function testCanRepresentInfoSeverity(): void
    {
        $issue = new ValidationIssue(
            'MX',
            'MX record looks good',
            Severity::Info,
        );

        $this->assertSame(Severity::Info, $issue->severity);
    }
}
