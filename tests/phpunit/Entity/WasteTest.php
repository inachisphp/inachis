<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\User;
use Inachis\Entity\Waste;
use DateTime;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class WasteTest extends TestCase
{
    protected ?Waste $waste;

    public function setUp(): void
    {
        $this->waste = new Waste();
        parent::setUp();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->waste->setId($uuid);
        $this->assertEquals($uuid, $this->waste->getId());
    }

    public function testSetAndGetSourceType(): void
    {
        $this->assertEmpty($this->waste->getSourceType());
        $this->waste->setSourceType('Image');
        $this->assertEquals('Image', $this->waste->getSourceType());
    }

    public function testSetAndGetSourceName(): void
    {
        $this->assertEmpty($this->waste->getSourceName());
        $this->waste->setSourceName('Image');
        $this->assertEquals('Image', $this->waste->getSourceName());
    }

    public function testSetAndGetTitle(): void
    {
        $this->assertEmpty($this->waste->getTitle());
        $this->waste->setTitle('Test');
        $this->assertEquals('Test', $this->waste->getTitle());
    }

    public function testSetAndGetContent(): void
    {
        $this->assertEmpty($this->waste->getContent());
        $this->waste->setContent('Test');
        $this->assertEquals('Test', $this->waste->getContent());
    }

    public function testSetAndGetModDate(): void
    {
        $date =  new DateTime();
        $this->waste->setModDate($date);
        $this->assertEquals($date, $this->waste->getModDate());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $this->waste->setUser($user);
        $this->assertEquals($user, $this->waste->getUser());
    }
}
