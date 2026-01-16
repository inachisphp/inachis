<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\Tag;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class TagTest extends TestCase
{
    protected ?Tag $tag;

    public function setUp(): void
    {
        $this->tag = new Tag();
        parent::setUp();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->tag->setId($uuid);
        $this->assertEquals($uuid, $this->tag->getId());
    }

    public function testSetAndGetTitle(): void
    {
        $this->tag->setTitle('test');
        $this->assertEquals('test', $this->tag->getTitle());
    }
}
