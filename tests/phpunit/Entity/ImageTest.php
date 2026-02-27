<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use DateTimeImmutable;
use Inachis\Entity\{Image, User};
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ImageTest extends TestCase
{
    protected ?Image $image;

    public function setUp(): void
    {
        $this->image = new Image();
        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->image->setId($uuid);
        $this->assertEquals($uuid, $this->image->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $this->image->setTitle('test');
        $this->assertEquals('test', $this->image->getTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $this->image->setDescription('test');
        $this->assertEquals('test', $this->image->getDescription());
    }

    public function testGetAndSetFilename(): void
    {
        $this->image->setFilename('test');
        $this->assertEquals('test', $this->image->getFilename());
    }

    public function testInvalidFiletype(): void
    {
        $this->assertFalse($this->image->isValidFiletype('test'));
    }

    public function testValidFiletype(): void
    {
        $this->assertTrue($this->image->isValidFiletype('image/jpeg'));
    }

    public function testGetAndSetFiletype(): void
    {
        $this->image->setFiletype('image/jpeg');
        $this->assertEquals('image/jpeg', $this->image->getFiletype());
        $this->expectException(FileException::class);
        $this->image->setFiletype('test');
    }

    public function testSetAndGetFilesize(): void
    {
        $this->image->setFilesize(100);
        $this->assertEquals(100, $this->image->getFilesize());
        $this->expectException(FileException::class);
        $this->image->setFilesize(-100);
    }

    public function testSetAndGetChecksum(): void
    {
        $this->image->setChecksum('test');
        $this->assertEquals('test', $this->image->getChecksum());
        $this->assertTrue($this->image->verifyChecksum('test'));
        $this->assertFalse($this->image->verifyChecksum('test123'));
    }

    public function testSetAndGetAuthor(): void
    {
        $this->image->setAuthor(new User('test'));
        $this->assertInstanceOf(User::class, $this->image->getAuthor());
        $this->assertEquals('test', $this->image->getAuthor()->getUsername());
    }

        public function testSetAndGetCreateDate(): void
    {
        $this->image->setCreateDate(new DateTimeImmutable('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->image->getCreateDate()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetModDate(): void
    {
        $this->image->setModDate(new DateTimeImmutable('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->image->getModDate()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetDimensionX(): void
    {
        $this->image->setDimensionX(100);
        $this->assertEquals(100, $this->image->getDimensionX());
    }

    public function testSetAndGetDimensionY(): void
    {
        $this->image->setDimensionY(100);
        $this->assertEquals(100, $this->image->getDimensionY());
    }

    public function testSetAndGetAltText(): void
    {
        $this->image->setAltText('test');
        $this->assertEquals('test', $this->image->getAltText());
    }
}
