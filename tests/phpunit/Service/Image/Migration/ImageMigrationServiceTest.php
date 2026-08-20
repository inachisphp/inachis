<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Image\Migration;

use Inachis\Service\Image\Migration\ImageProcessor;
use Inachis\Service\Image\Migration\MarkdownImageRewriter;
use PHPUnit\Framework\TestCase;

class ImageMigrationServiceTest extends TestCase
{
    public function testMarkdownImageRewriterExtractsReferences(): void
    {
        $rewriter = new MarkdownImageRewriter();
        $content = 'Here is an image ![photo](/imgs/hartland-quay.jpg?width=500#sec) and another <img src="/imgs/lighthouse.png">';

        $refs = $rewriter->extractImageReferences($content);

        $this->assertCount(2, $refs);
        $this->assertContains('hartland-quay.jpg', $refs);
        $this->assertContains('lighthouse.png', $refs);
    }

    public function testMarkdownImageRewriterReplacesUrlsSafely(): void
    {
        $rewriter = new MarkdownImageRewriter();
        $content = 'Check /imgs/hartland-quay.jpg and /imgs/quay.jpg';

        $updated = $rewriter->rewriteContent($content, [
            'quay.jpg' => 'quay-new.webp',
            'hartland-quay.jpg' => 'hartland-quay-new.webp',
        ]);

        $this->assertStringContainsString('/imgs/hartland-quay-new.webp', $updated);
        $this->assertStringContainsString('/imgs/quay-new.webp', $updated);
        $this->assertStringNotContainsString('/imgs/hartland-quay.jpg', $updated);
    }

    public function testImageProcessorDetectsMimeType(): void
    {
        $processor = new ImageProcessor();
        $mime = $processor->detectMimeType(__FILE__);

        $this->assertNotEmpty($mime);
    }
}
