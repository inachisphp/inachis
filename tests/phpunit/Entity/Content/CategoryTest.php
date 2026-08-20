<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use Inachis\Entity\Content\Category;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class CategoryTest extends TestCase
{
    protected Category $category;

    protected function setUp(): void
    {
        $this->category = new Category();

        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();

        $this->category->setId($uuid);

        $this->assertSame($uuid, $this->category->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $this->category->setTitle('test');

        $this->assertSame('test', $this->category->getTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $this->category->setDescription('test');

        $this->assertSame('test', $this->category->getDescription());
    }

    public function testGetAndSetParent(): void
    {
        $parent = new Category('Parent');

        $this->category->setParent($parent);

        $this->assertSame($parent, $this->category->getParent());
        $this->assertSame('Parent', $this->category->getParent()?->getTitle());
    }

    public function testCannotBeOwnParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category cannot be its own parent');

        $this->category->setParent($this->category);
    }

    public function testAddChild(): void
    {
        $child = new Category('Child');

        $this->category->addChild($child);

        $this->assertCount(1, $this->category->getChildren());
        $this->assertTrue($this->category->getChildren()->contains($child));
        $this->assertSame($this->category, $child->getParent());
    }

    public function testAddChildDoesNotDuplicate(): void
    {
        $child = new Category('Child');

        $this->category->addChild($child);
        $this->category->addChild($child);

        $this->assertCount(1, $this->category->getChildren());
    }

    public function testRemoveChild(): void
    {
        $child = new Category('Child');

        $this->category->addChild($child);

        $this->assertCount(1, $this->category->getChildren());
        $this->assertSame($this->category, $child->getParent());

        $this->category->removeChild($child);

        $this->assertCount(0, $this->category->getChildren());
        $this->assertNull($child->getParent());
    }

    public function testIsRootCategory(): void
    {
        $this->assertTrue($this->category->isRootCategory());

        $this->category->setParent(new Category('Parent'));

        $this->assertFalse($this->category->isRootCategory());
    }

    public function testIsChildCategory(): void
    {
        $this->assertFalse($this->category->isChildCategory());

        $this->category->setParent(new Category('Parent'));

        $this->assertTrue($this->category->isChildCategory());
    }

    public function testGetFullPathForRootCategory(): void
    {
        $this->category->setTitle('Root');

        $this->assertSame('Root', $this->category->getFullPath());
    }

    public function testGetFullPathForChildCategory(): void
    {
        $parent = new Category('Darth Vader');
        $child = new Category('Luke Skywalker');

        $parent->addChild($child);

        $this->assertSame(
            'Darth Vader/Luke Skywalker',
            $child->getFullPath(),
        );
    }

    public function testSetAndGetVisible(): void
    {
        $this->assertTrue($this->category->isVisible());

        $this->category->setVisible(false);

        $this->assertFalse($this->category->isVisible());

        $this->category->setVisible(true);

        $this->assertTrue($this->category->isVisible());
    }
}
