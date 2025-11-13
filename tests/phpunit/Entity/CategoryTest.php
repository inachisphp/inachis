<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Entity;

use App\Entity\Category;
use App\Entity\Image;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class CategoryTest extends TestCase
{
    protected Category $category;

    public function setUp(): void
    {
        $this->category = new Category();
        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->category->setId($uuid);
        $this->assertEquals($uuid->toString(), $this->category->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $this->category->setTitle('test');
        $this->assertEquals('test', $this->category->getTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $this->category->setDescription('test');
        $this->assertEquals('test', $this->category->getDescription());
    }

    public function testGetAndSetImage(): void
    {
        $image = new Image();
        $this->category->setImage($image);
        $this->assertEquals($image, $this->category->getImage());
        $this->category->setImage(null);
        $this->assertEquals(null, $this->category->getImage());
    }

    public function testGetAndSetIcon(): void
    {
        $image = new Image();
        $this->category->setIcon($image);
        $this->assertEquals($image, $this->category->getIcon());
    }

    public function testGetAndSetParent(): void
    {
        $this->category->setParent(new Category('test-parent'));
        $this->assertEquals('test-parent', $this->category->getParent()->getTitle());
    }

    public function testAddChild(): void
    {
        $this->category->addChild(new Category('first child'));
        $this->assertNotEmpty($this->category->getChildren());
    }

    public function testIsRootCategory(): void
    {
        $this->assertTrue($this->category->isRootCategory());
        $this->category->setParent(new Category('Darth Vader'));
        $this->assertFalse($this->category->isRootCategory());
    }

    public function testHasImage(): void
    {
        $this->assertFalse($this->category->hasImage());
        $this->category->setImage(new Image());
        $this->assertTrue($this->category->hasImage());
    }

    public function testHasIcon(): void
    {
        $image = new Image();
        $this->assertFalse($this->category->hasIcon());
        $this->category->setIcon($image);
        $this->assertTrue($this->category->hasIcon());
    }

    public function testGetFullPath(): void
    {
        $this->category->setTitle('Darth Vader');
        $this->category->addChild(new Category('Luke Skywalker'));
        $this->category->getChildren()[0]->setParent(new Category('Darth Vader'));
        $this->assertEquals(
            'Darth Vader/Luke Skywalker',
            $this->category->getChildren()[0]->getFullPath()
        );
    }

    public function testSetAndGetVisible(): void
    {
        $this->assertTrue($this->category->isVisible());
        $this->category->setVisible(false);
        $this->assertFalse($this->category->isVisible());
    }
}
