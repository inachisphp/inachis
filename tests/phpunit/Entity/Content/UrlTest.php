<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use DateTimeImmutable;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UrlTest extends TestCase
{
    private Url $url;
    private Page $page;

    protected function setUp(): void
    {
        $this->page = new Page();
        $this->url = new Url($this->page);
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid4();

        $this->url->setId($uuid);

        $this->assertSame($uuid, $this->url->getId());
    }

    public function testSetAndGetLink(): void
    {
        $this->url->setLink('test');

        $this->assertSame('test', $this->url->getLink());
    }

    public function testSetAndGetLinkCanonical(): void
    {
        $this->url->setLink('test');

        $this->assertSame(md5('test'), $this->url->getLinkCanonical());
    }

    public function testGetPath(): void
    {
        $this->url->setLink('my-page');

        $this->assertSame('/my-page', $this->url->getPath());
    }

    public function testGetPathRemovesLeadingSlash(): void
    {
        $this->url->setLink('/my-page');

        $this->assertSame('/my-page', $this->url->getPath());
    }

    public function testSetUpdatedAtToNow(): void
    {
        $this->url->setUpdatedAt(
            new DateTimeImmutable('2000-01-01')
        );

        $this->url->setUpdatedAtToNow();

        $this->assertSame(
            (new DateTimeImmutable())->format('Ymd'),
            $this->url->getUpdatedAt()->format('Ymd')
        );
    }

    public function testValidateUrl(): void
    {
        $this->url->setLink('test-link');

        $this->assertTrue($this->url->validateURL());

        $this->url->setLink("test's-link");

        $this->assertFalse($this->url->validateURL());
    }

    public function testOnPrePersistSetsDates(): void
    {
        $this->url->onPrePersist();

        $this->assertInstanceOf(
            DateTimeImmutable::class,
            $this->url->getCreatedAt()
        );

        $this->assertInstanceOf(
            DateTimeImmutable::class,
            $this->url->getUpdatedAt()
        );
    }

    public function testSetAndGetCreatedAt(): void
    {
        $date = new DateTimeImmutable('2025-01-01');

        $this->url->setCreatedAt($date);

        $this->assertSame($date, $this->url->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $date = new DateTimeImmutable('2025-02-01');

        $this->url->setUpdatedAt($date);

        $this->assertSame($date, $this->url->getUpdatedAt());
    }

    public function testGetContent(): void
    {
        $this->assertSame($this->page, $this->url->getContent());
    }

    public function testSetContent(): void
    {
        $newPage = new Page();

        $this->url->setContent($newPage);

        $this->assertSame($newPage, $this->url->getContent());
    }

    public function testIsDefault(): void
    {
        $this->assertTrue($this->url->isDefault());

        $this->url->setDefault(false);

        $this->assertFalse($this->url->isDefault());
    }

    public function testConstructorCanCreateNonDefaultUrl(): void
    {
        $url = new Url($this->page, 'example', false);

        $this->assertFalse($url->isDefault());
    }

    public function testAssociateContentAddsUrlToPage(): void
    {
        $this->assertTrue(
            $this->page->getUrls()->contains($this->url)
        );
    }

    public function testOnPreUpdateUpdatesTimestamp(): void
    {
        $old = new DateTimeImmutable('2000-01-01');

        $this->url->setUpdatedAt($old);
        $this->url->onPreUpdate();

        $this->assertGreaterThan(
            $old->getTimestamp(),
            $this->url->getUpdatedAt()->getTimestamp()
        );
    }
}
