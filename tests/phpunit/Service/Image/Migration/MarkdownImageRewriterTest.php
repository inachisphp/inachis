<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Image\Migration;

use Inachis\Service\Image\Migration\MarkdownImageRewriter;
use PHPUnit\Framework\TestCase;

final class MarkdownImageRewriterTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new MarkdownImageRewriter();

        self::assertInstanceOf(
            MarkdownImageRewriter::class,
            $instance
        );
    }
}