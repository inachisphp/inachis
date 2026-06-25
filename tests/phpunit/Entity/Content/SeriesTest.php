<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Inachis\Entity\Content\{Page, Series};
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SeriesTest extends TestCase
{
    protected ?Series $series;

    public function setUp(): void
    {
        $this->series = new Series();
        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->series->setId($uuid);
        $this->assertEquals($uuid, $this->series->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $this->series->setTitle('test');
        $this->assertEquals('test', $this->series->getTitle());
    }

    public function testGetAndSetSubTitle(): void
    {
        $this->series->setSubTitle('test');
        $this->assertEquals('test', $this->series->getSubTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $this->series->setDescription('test');
        $this->assertEquals('test', $this->series->getDescription());
    }

    public function testGetAndSetUrl(): void
    {
        $this->series->setUrl('test');
        $this->assertEquals('test', $this->series->getUrl());
        // @todo add checks for invalid URLs
    }

    public function testGetAndSetFirstDate(): void
    {
        $testDate = new DateTimeImmutable();
        $this->series->setFirstDate($testDate);
        $this->assertEquals($testDate, $this->series->getFirstDate());
    }

    public function testGetAndSetLastDate(): void
    {
        $testDate = new DateTimeImmutable();
        $this->series->setLastDate($testDate);
        $this->assertEquals($testDate, $this->series->getLastDate());
    }

    public function testSetAndGetAuthor(): void
    {
        $this->series->setAuthor(new User('test'));
        $this->assertInstanceOf(User::class, $this->series->getAuthor());
        $this->assertEquals('test', $this->series->getAuthor()->getUsername());
    }

    public function testSetAndGetCreateDate(): void
    {
        $date = new DateTimeImmutable('now');
        $this->series->setCreatedAt($date);
        $this->assertEquals($date, $this->series->getCreatedAt());
    }

    public function testSetAndGetModDate(): void
    {
        $date = new DateTimeImmutable('now');
        $this->series->setUpdatedAt($date);
        $this->assertEquals($date, $this->series->getUpdatedAt());
    }

    public function testSetAndGetImage(): void
    {
        $image = new Image();
        $this->assertEmpty($this->series->getImage());
        $this->series->setImage($image);
        $this->assertEquals($image, $this->series->getImage());
    }

    public function testSetAndGetVisibility(): void
    {
        $this->series->setVisible(true);
        $this->assertEquals(true, $this->series->isVisible());
        $this->series->setVisible();
        $this->assertEquals(false, $this->series->isVisible());
    }
}
