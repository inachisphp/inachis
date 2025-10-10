<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SeriesTest extends TestCase
{
    protected ?Series $series;

    public function setUp() : void
    {
        $this->series = new Series();
        parent::setUp();
    }

    public function testGetAndSetId() : void
    {
        $uuid = Uuid::uuid1();
        $this->series->setId($uuid);
        $this->assertEquals($uuid, $this->series->getId());
    }

    public function testGetAndSetTitle() : void
    {
        $this->series->setTitle('test');
        $this->assertEquals('test', $this->series->getTitle());
    }

    public function testGetAndSetSubTitle() : void
    {
        $this->series->setSubTitle('test');
        $this->assertEquals('test', $this->series->getSubTitle());
    }

    public function testGetAndSetDescription() : void
    {
        $this->series->setDescription('test');
        $this->assertEquals('test', $this->series->getDescription());
    }

    public function testGetAndSetUrl() : void
    {
        $this->series->setUrl('test');
        $this->assertEquals('test', $this->series->getUrl());
        // @todo add checks for invalid URLs
    }

    public function testGetAndSetFirstDate() : void
    {
        $testDate = new DateTime();
        $this->series->setFirstDate($testDate);
        $this->assertEquals($testDate, $this->series->getFirstDate());
    }

    public function testGetAndSetLastDate() : void
    {
        $testDate = new DateTime();
        $this->series->setLastDate($testDate);
        $this->assertEquals($testDate, $this->series->getLastDate());
    }

    public function testGetAndSetItems() : void
    {
        $this->series->setItems(new ArrayCollection([
            'test1',
            'test2',
        ]));
        $this->assertCount(2, $this->series->getItems());
        $this->series->addItem(new Page('test'));
        $this->assertCount(3, $this->series->getItems());
    }

    public function testSetAndGetCreateDate() : void
    {
        $date = new DateTime('now');
        $this->series->setCreateDate($date);
        $this->assertEquals($date, $this->series->getCreateDate());
    }

    public function testSetAndGetModDate() : void
    {
        $date = new DateTime('now');
        $this->series->setModDate($date);
        $this->assertEquals($date, $this->series->getModDate());
    }

    public function testSetAndGetImage() : void
    {
        $image = new Image();
        $this->assertEmpty($this->series->getImage());
        $this->series->setImage($image);
        $this->assertEquals($image, $this->series->getImage());
    }

    public function testSetAndGetVisibility() : void
    {
        $this->series->setVisibility(Series::VIS_PUBLIC);
        $this->assertEquals(Series::VIS_PUBLIC, $this->series->getVisibility());
        $this->series->setVisibility();
        $this->assertEquals(Series::VIS_PRIVATE, $this->series->getVisibility());
    }
}
