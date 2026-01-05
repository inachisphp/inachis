<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\Image;

use Inachis\Service\Image\ImageExtractor;
use PHPUnit\Framework\TestCase;

class ImageExtractorTest extends TestCase
{
    private ImageExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ImageExtractor();
    }

    public function testExtractsSingleImage(): void
    {
        $content = '![Alt text](https://example.com/image.png)';
        $images = $this->extractor->extractFromContent($content);

        $this->assertCount(1, $images);
        $this->assertEquals('https://example.com/image.png', $images[0]);
    }

    public function testExtractsMultipleImages(): void
    {
        $content = '![A](https://a.com/a.jpg) some text ![B](https://b.com/b.png)';
        $images = $this->extractor->extractFromContent($content);

        $this->assertCount(2, $images);
        $this->assertEquals(['https://a.com/a.jpg', 'https://b.com/b.png'], $images);
    }

    public function testReturnsEmptyArrayWhenNoImages(): void
    {
        $content = 'No images here!';
        $images = $this->extractor->extractFromContent($content);

        $this->assertEmpty($images);
    }
}
