<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Media;

use Inachis\Entity\Media\Download;
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

    public function testSetAndGetCreatedAt(): void
    {
        $this->download->setCreatedAt(new \DateTimeImmutable('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->download->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $this->download->setUpdatedAt(new \DateTimeImmutable('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->download->getUpdatedAt()->format('Y-m-d H:i:s'));
    }
}
