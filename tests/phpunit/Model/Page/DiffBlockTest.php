<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Enum\DiffBlockType;
use Inachis\Model\Page\DiffBlock;
use PHPUnit\Framework\TestCase;

final class DiffBlockTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $block = new DiffBlock(
            DiffBlockType::REPLACED,
            '<ins>New content</ins>',
            '<del>Old content</del>',
        );

        $this->assertSame(DiffBlockType::REPLACED, $block->type);
        $this->assertSame('<ins>New content</ins>', $block->html);
        $this->assertSame('<del>Old content</del>', $block->oldHtml);
    }

    public function testOldHtmlDefaultsToNull(): void
    {
        $block = new DiffBlock(
            DiffBlockType::INSERTED,
            '<ins>Added content</ins>',
        );

        $this->assertSame(DiffBlockType::INSERTED, $block->type);
        $this->assertSame('<ins>Added content</ins>', $block->html);
        $this->assertNull($block->oldHtml);
    }
}
