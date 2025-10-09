<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Page;
use App\Entity\Series;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    protected $series;

    public function setUp() : void
    {
        $this->series = new Series();

        parent::setUp();
    }

    public function testGetAndSetTitle()
    {
        $this->series->setTitle('test');
        $this->assertEquals('test', $this->series->getTitle());
    }

    public function testGetAndSetSubTitle()
    {
        $this->series->setSubTitle('test');
        $this->assertEquals('test', $this->series->getSubTitle());
    }

    public function testGetAndSetDescription()
    {
        $this->series->setDescription('test');
        $this->assertEquals('test', $this->series->getDescription());
    }

    public function testGetAndSetFirstDate()
    {
        $testDate = new \DateTime();
        $this->series->setFirstDate($testDate);
        $this->assertEquals($testDate, $this->series->getFirstDate());
    }

    public function testGetAndSetLastDate()
    {
        $testDate = new \DateTime();
        $this->series->setLastDate($testDate);
        $this->assertEquals($testDate, $this->series->getLastDate());
    }

    public function testGetAndSetItems()
    {
        $this->series->setItems([
            'test1',
            'test2',
        ]);
        $this->assertCount(2, $this->series->getItems());
        $this->series->addItem(new Page('test'));
        $this->assertCount(3, $this->series->getItems());
    }

    public function testSetAndGetCreateDate()
    {
        $date = new \DateTime('now');
        $this->series->setCreateDate($date);
        $this->assertEquals($date, $this->series->getCreateDate());
    }

    public function testSetAndGetModDate()
    {
        $date = new \DateTime('now');
        $this->series->setModDate($date);
        $this->assertEquals($date, $this->series->getModDate());
    }
}
