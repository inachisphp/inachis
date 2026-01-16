<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\Download;
use DateTime;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DownloadTest extends TestCase
{
    protected ?Download $download;

    public function setUp(): void
    {
        $this->download = new Download();
        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->download->setId($uuid);
        $this->assertEquals($uuid, $this->download->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $this->download->setTitle('test');
        $this->assertEquals('test', $this->download->getTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $this->download->setDescription('test');
        $this->assertEquals('test', $this->download->getDescription());
    }

    public function testGetAndSetFilename(): void
    {
        $this->download->setFilename('test');
        $this->assertEquals('test', $this->download->getFilename());
    }

    public function testValidFiletype(): void
    {
        $this->assertTrue($this->download->isValidFiletype('something/anything'));
    }

    public function testGetAndSetFiletype(): void
    {
        $this->download->setFiletype('image/jpeg');
        $this->assertEquals('image/jpeg', $this->download->getFiletype());
    }

    public function testSetAndGetFilesize(): void
    {
        $this->download->setFilesize(100);
        $this->assertEquals(100, $this->download->getFilesize());
        $this->expectException(FileException::class);
        $this->download->setFilesize(-100);
    }

    public function testSetAndGetChecksum(): void
    {
        $this->download->setChecksum('test');
        $this->assertEquals('test', $this->download->getChecksum());
        $this->assertTrue($this->download->verifyChecksum('test'));
        $this->assertFalse($this->download->verifyChecksum('test123'));
    }

    public function testSetAndGetCreateDate(): void
    {
        $this->download->setCreateDate(new DateTime('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->download->getCreateDate()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetModDate(): void
    {
        $this->download->setModDate(new DateTime('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->download->getModDate()->format('Y-m-d H:i:s'));
    }
}
