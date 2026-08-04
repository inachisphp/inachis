<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use Inachis\Entity\Content\Tag;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class TagTest extends TestCase
{
    private Tag $tag;

    protected function setUp(): void
    {
        $this->tag = new Tag('Test Tag');
    }

    public function testConstructorSetsTitleAndSlug(): void
    {
        $this->assertSame('test tag', $this->tag->getTitle());
        $this->assertSame('test-tag', $this->tag->getSlug());
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid4();

        $this->tag->setId($uuid);

        $this->assertSame($uuid, $this->tag->getId());
    }

    public function testSetAndGetTitle(): void
    {
        $this->tag->setTitle('My New Tag');

        $this->assertSame('my new tag', $this->tag->getTitle());
    }

    public function testSetTitleUpdatesSlug(): void
    {
        $this->tag->setTitle('My New Tag');

        $this->assertSame('my-new-tag', $this->tag->getSlug());
    }

    public function testTitleIsTrimmed(): void
    {
        $this->tag->setTitle('   My Tag   ');

        $this->assertSame('my tag', $this->tag->getTitle());
        $this->assertSame('my-tag', $this->tag->getSlug());
    }

    public function testEmptyTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag title cannot be empty');

        $this->tag->setTitle('');
    }

    public function testWhitespaceTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag title cannot be empty');

        $this->tag->setTitle('      ');
    }

    public function testSlugHandlesSpecialCharacters(): void
    {
        $this->tag->setTitle('Café & Restaurant');

        $this->assertSame('café & restaurant', $this->tag->getTitle());
        $this->assertSame('cafe-restaurant', $this->tag->getSlug());
    }

    public function testToStringReturnsTitle(): void
    {
        $this->assertSame('test tag', (string) $this->tag);
    }
}
