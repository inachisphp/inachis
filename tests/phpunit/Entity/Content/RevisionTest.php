<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RevisionTest extends TestCase
{
    private Revision $revision;

    protected function setUp(): void
    {
        $this->revision = new Revision();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid4();

        $this->revision->setId($uuid);

        $this->assertSame($uuid, $this->revision->getId());
    }

    public function testGetAndSetPage(): void
    {
        $page = new Page();

        $this->revision->setPage($page);

        $this->assertSame($page, $this->revision->getPage());
    }

    public function testPageCanBeNull(): void
    {
        $this->revision->setPage(null);

        $this->assertNull($this->revision->getPage());
    }

    public function testGetAndSetVersionNumber(): void
    {
        $this->revision->setVersionNumber(223);

        $this->assertSame(223, $this->revision->getVersionNumber());
    }

    public function testVersionNumberMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Version number must be greater than 0');

        $this->revision->setVersionNumber(0);
    }

    public function testGetAndSetCreatedAt(): void
    {
        $date = new \DateTimeImmutable('2025-01-01 12:34:56');

        $this->revision->setCreatedAt($date);

        $this->assertSame($date, $this->revision->getCreatedAt());
    }

    public function testOnPrePersistSetsCreatedAt(): void
    {
        $this->revision->onPrePersist();

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $this->revision->getCreatedAt(),
        );
    }

    public function testGetAndSetUser(): void
    {
        $user = new User();

        $this->revision->setUser($user);

        $this->assertSame($user, $this->revision->getUser());
    }

    public function testUserCanBeNull(): void
    {
        $this->revision->setUser(null);

        $this->assertNull($this->revision->getUser());
    }

    public function testGetAndSetAction(): void
    {
        $this->revision->setAction('Updated content');

        $this->assertSame(
            'Updated content',
            $this->revision->getAction(),
        );
    }

    public function testGetAndSetTitle(): void
    {
        $this->revision->setTitle('Test title');

        $this->assertSame(
            'Test title',
            $this->revision->getTitle(),
        );
    }

    public function testGetAndSetSubTitle(): void
    {
        $this->revision->setSubTitle('Test subtitle');

        $this->assertSame(
            'Test subtitle',
            $this->revision->getSubTitle(),
        );
    }

    public function testSubTitleCanBeNull(): void
    {
        $this->revision->setSubTitle(null);

        $this->assertNull($this->revision->getSubTitle());
    }

    public function testGetAndSetContent(): void
    {
        $this->revision->setContent('Test content');

        $this->assertSame(
            'Test content',
            $this->revision->getContent(),
        );
    }

    public function testContentCanBeNull(): void
    {
        $this->revision->setContent(null);

        $this->assertNull($this->revision->getContent());
    }
}
