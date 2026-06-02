<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Validator;

use PHPUnit\Framework\TestCase;
use Inachis\Validator\CaaValidator;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

final class CaaValidatorTest extends TestCase
{
    public function testValidateWithNoRecords(): void
    {
        $validator = new CaaValidator();

        $issues = $validator->validate([]);

        $this->assertCount(0, $issues);
    }

    public function testValidateWithInvalidRecords(): void
    {
        $validator = new CaaValidator();

        $issues = $validator->validate([
            [
                'target' => 'example.com',
                'priority' => 10,
                // 'value' is missing
            ]
        ]);

        $this->assertCount(1, $issues);
        $this->assertInstanceOf(ValidationIssue::class, $issues[0]);
        $this->assertSame('caa', $issues[0]->getType());
        $this->assertSame(
            'Malformed CAA record',
            $issues[0]->getMessage()
        );
        $this->assertSame(Severity::Error, $issues[0]->getSeverity());
    }

    public function testValidateWithValidRecords(): void
    {
        $validator = new CaaValidator();

        $records = [
            [
                'target' => 'example.com',
                'priority' => 10,
                'value' => 'caa record 1'
            ]
        ];

        $issues = $validator->validate($records);

        $this->assertCount(0, $issues);
    }
}