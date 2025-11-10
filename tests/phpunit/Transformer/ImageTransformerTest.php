<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Transformer;

use App\Transformer\ImageTransformer;
use PHPUnit\Framework\TestCase;

class ImageTransformerTest extends TestCase
{
    private ImageTransformer $imageTransformer;

    protected function setUp(): void
    {
        $this->imageTransformer = new ImageTransformer();
    }

    public function testIsHEICSupported(): void
    {
        $this->assertIsBool($this->imageTransformer->isHEICSupported());
    }
}