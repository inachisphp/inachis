<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Validator;

use PHPUnit\Framework\TestCase;
use Inachis\Validator\BimiValidator;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

final class BimiValidatorTest extends TestCase
{
    public function testNoRecordsWithRejectPolicyTriggersWarning(): void
    {
        $validator = new BimiValidator();

        $issues = $validator->validate([], 'v=DMARC1; p=reject;');

        $this->assertCount(1, $issues);
        $this->assertInstanceOf(ValidationIssue::class, $issues[0]);
        $this->assertSame('bimi', $issues[0]->type);
        $this->assertSame(
            'No BIMI record found (required when DMARC is reject)',
            $issues[0]->message
        );
        $this->assertSame(Severity::Warning, $issues[0]->severity);
    }

    public function testValidBimiRecordPasses(): void
    {
        $validator = new BimiValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'v=BIMI1; l=https://example.com/logo.svg;'
            ]
        ];

        $issues = $validator->validate($records, 'v=DMARC1; p=reject;');

        $this->assertCount(0, $issues);
    }

    public function testInvalidBimiRecordTriggersError(): void
    {
        $validator = new BimiValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'invalid-bimi-record'
            ]
        ];

        $issues = $validator->validate($records, 'v=DMARC1; p=reject;');

        $this->assertCount(1, $issues);
        $this->assertSame('bimi', $issues[0]->type);
        $this->assertStringContainsString('Invalid BIMI record format', $issues[0]->message);
        $this->assertSame(Severity::Error, $issues[0]->severity);
    }

    public function testNoValidationWhenPolicyIsNotReject(): void
    {
        $validator = new BimiValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'txt' => 'invalid-bimi-record'
            ]
        ];

        $issues = $validator->validate($records, 'v=DMARC1; p=none;');

        $this->assertCount(0, $issues);
    }

    public function testMissingTxtKeyHandledAsInvalid(): void
    {
        $validator = new BimiValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10
                // no 'txt'
            ]
        ];

        $issues = $validator->validate($records, 'v=DMARC1; p=reject;');

        $this->assertCount(1, $issues);
        $this->assertSame(Severity::Error, $issues[0]->severity);
    }
}