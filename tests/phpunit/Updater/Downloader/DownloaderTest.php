<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater\Downloader;

use Inachis\Updater\Downloader\Downloader;
use PHPUnit\Framework\TestCase;

final class DownloaderTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new Downloader();

        self::assertInstanceOf(
            Downloader::class,
            $instance
        );
    }
}