<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Image\Migration;

use Inachis\Service\Image\Migration\ImageProcessor;
use PHPUnit\Framework\TestCase;

final class ImageProcessorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ImageProcessor();

        self::assertInstanceOf(
            ImageProcessor::class,
            $instance,
        );
    }
}
