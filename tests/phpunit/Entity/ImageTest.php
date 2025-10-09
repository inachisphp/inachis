<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Image;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ImageTest extends TestCase
{
    protected ?Image $image;

    public function setUp() : void
    {
        $this->image = new Image();

        parent::setUp();
    }

    public function testGetAndSetId()
    {
        $uuid = Uuid::uuid1();
        $this->image->setId($uuid);
        $this->assertEquals($uuid, $this->image->getId());
    }

    public function testGetAndSetTitle()
    {
        $this->image->setTitle('test');
        $this->assertEquals('test', $this->image->getTitle());
    }

    public function testGetAndSetDescription()
    {
        $this->image->setDescription('test');
        $this->assertEquals('test', $this->image->getDescription());
    }

    public function testGetAndSetFilename()
    {
        $this->image->setFilename('test');
        $this->assertEquals('test', $this->image->getFilename());
    }

    public function testInvalidFiletype()
    {
        $this->assertFalse($this->image->isValidFiletype('test'));
    }

    public function testValidFiletype()
    {
        $this->assertTrue($this->image->isValidFiletype('image/jpeg'));
    }

    public function testGetAndSetFiletype()
    {
        $this->image->setFiletype('image/jpeg');
        $this->assertEquals('image/jpeg', $this->image->getFiletype());
        $this->expectException(FileException::class);
        $this->image->setFiletype('test');
    }

    public function testSetAndGetFilesize()
    {
        $this->image->setFilesize(100);
        $this->assertEquals(100, $this->image->getFilesize());
        $this->expectException(FileException::class);
        $this->image->setFilesize(-100);
    }

    public function testSetAndGetChecksum()
    {
        $this->image->setChecksum('test');
        $this->assertEquals('test', $this->image->getChecksum());
        $this->assertTrue($this->image->verifyChecksum('test'));
        $this->assertFalse($this->image->verifyChecksum('test123'));
    }

    public function testSetAndGetCreateDate()
    {
        $this->image->setCreateDate(new \DateTime('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->image->getCreateDate()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetModDate()
    {
        $this->image->setModDate(new \DateTime('1970-01-02 01:34:56'));
        $this->assertEquals('1970-01-02 01:34:56', $this->image->getModDate()->format('Y-m-d H:i:s'));
    }

    public function testSetAndGetDimensionX()
    {
        $this->image->setDimensionX(100);
        $this->assertEquals(100, $this->image->getDimensionX());
    }

    public function testSetAndGetDimensionY()
    {
        $this->image->setDimensionY(100);
        $this->assertEquals(100, $this->image->getDimensionY());
    }

    public function testSetAndGetAltText()
    {
        $this->image->setAltText('test');
        $this->assertEquals('test', $this->image->getAltText());
    }
}
