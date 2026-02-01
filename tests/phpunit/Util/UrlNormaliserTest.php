<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Util;

use Inachis\Util\UrlNormaliser;
use PHPUnit\Framework\TestCase;
use TypeError;

class UrlNormaliserTest extends TestCase
{
    public function testFromUri(): void
    {
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('some short text'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('Some Short Text'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('some  short  text'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('some short text'));
        $this->assertEquals('s0me-short-text', UrlNormaliser::toUri('s0me short text'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('some short text $%'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('$ some short text'));
        $this->assertEquals('some-short-text', UrlNormaliser::toUri('some_short text'));
        $this->assertEquals('some-hort-text', UrlNormaliser::toUri('some $hort text'));
        $this->assertEquals('some', UrlNormaliser::toUri('some short text', 5));
        $this->assertEquals('some-s', UrlNormaliser::toUri('some short text', 6));
        $this->assertEquals('rock-and-roll', UrlNormaliser::toUri('rock & roll'));
    }

    public function testFromUriException(): void
    {
        $this->expectException(TypeError::class);
        UrlNormaliser::toUri(['string-in-an-array']);
    }

    public function testToUri(): void
    {
        $this->assertEquals(
            'something-really-cool',
            UrlNormaliser::fromUri('https://something.local/2018/03/12/something-really-cool')
        );
        $this->assertEquals(
            'something-really-cool',
            UrlNormaliser::fromUri('/2018/03/12/something-really-cool')
        );
        $this->assertEquals('something-really-cool', UrlNormaliser::fromUri('something-really-cool'));
        $this->assertEquals('something-really-cool', UrlNormaliser::fromUri('something-really-cool/'));
    }
}
