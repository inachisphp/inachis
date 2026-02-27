<?php

namespace Inachis\Tests\Diagnostics;

use Inachis\Diagnostics\CheckResult;
use PHPUnit\Framework\TestCase;

final class CheckResultTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $id = 'test_check';
        $label = 'Test Check';
        $status = 'ok';
        $value = 'some value';
        $details = 'details';
        $recommendation = 'recommendation';
        $section = 'test';
        $confidence = 'high';

        $result = new CheckResult(
            $id,
            $label,
            $status,
            $value,
            $details,
            $recommendation,
            $section,
            $confidence
        );

        $this->assertSame($id, $result->id);
        $this->assertSame($label, $result->label);
        $this->assertSame($status, $result->status);
        $this->assertSame($value, $result->value);
        $this->assertSame($details, $result->details);
        $this->assertSame($recommendation, $result->recommendation);
        $this->assertSame($section, $result->section);
        $this->assertSame($confidence, $result->confidence);
    }
}
