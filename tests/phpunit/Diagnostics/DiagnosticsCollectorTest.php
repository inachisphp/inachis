<?php

namespace Inachis\Tests\Diagnostics;

use Inachis\Diagnostics\CheckResult;
use Inachis\Diagnostics\DiagnosticsCollector;
use Inachis\Diagnostics\CheckInterface;
use PHPUnit\Framework\TestCase;

final class DiagnosticsCollectorTest extends TestCase
{
    public function testCollect(): void
    {
        $check1 = $this->createMock(CheckInterface::class);
        $result1 = new CheckResult('id1', 'label1', 'ok', null, 'details', null, 'section1', 'high');
        $check1->expects($this->once())->method('run')->willReturn($result1);

        $check2 = $this->createMock(CheckInterface::class);
        $result2 = new CheckResult('id2', 'label2', 'warning', 'val', 'details', 'rec', 'section2', 'medium');
        $check2->expects($this->once())->method('run')->willReturn($result2);

        $collector = new DiagnosticsCollector([$check1, $check2]);
        $results = $collector->collect();

        $this->assertCount(2, $results);
        $this->assertSame($result1, $results[0]);
        $this->assertSame($result2, $results[1]);
    }

    public function testGrouped(): void
    {
        $check1 = $this->createMock(CheckInterface::class);
        $result1 = new CheckResult('id1', 'label1', 'ok', null, 'details', null, 'SectionA', 'high');
        $check1->expects($this->once())->method('run')->willReturn($result1);

        $check2 = $this->createMock(CheckInterface::class);
        $result2 = new CheckResult('id2', 'label2', 'warning', 'val', 'details', 'rec', 'SectionA', 'medium');
        $check2->expects($this->once())->method('run')->willReturn($result2);

        $check3 = $this->createMock(CheckInterface::class);
        $result3 = new CheckResult('id3', 'label3', 'error', null, 'details', null, 'SectionB', 'low');
        $check3->expects($this->once())->method('run')->willReturn($result3);

        $collector = new DiagnosticsCollector([$check1, $check2, $check3]);
        $groups = $collector->grouped();

        $this->assertArrayHasKey('SectionA', $groups);
        $this->assertArrayHasKey('SectionB', $groups);
        
        $this->assertSame('SectionA', $groups['SectionA']['label']);
        $this->assertCount(2, $groups['SectionA']['checks']);
        $this->assertSame($result1, $groups['SectionA']['checks'][0]);
        $this->assertSame($result2, $groups['SectionA']['checks'][1]);

        $this->assertSame('SectionB', $groups['SectionB']['label']);
        $this->assertCount(1, $groups['SectionB']['checks']);
        $this->assertSame($result3, $groups['SectionB']['checks'][0]);
    }
}
