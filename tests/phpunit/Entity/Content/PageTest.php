<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Inachis\Entity\Media\Image;
use Inachis\Entity\Content\{Category, Page, Series, Tag, Url};
use Inachis\Entity\User\User;
use Inachis\Enum\EditorialStatus;
use Inachis\Exception\InvalidTimezoneException;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class PageTest extends TestCase
{
    protected Page $page;

    public function setUp(): void
    {
        $this->page = new Page();

        parent::setUp();
    }

    public function testSetAndGetLatlong(): void
    {
        $this->page->setLatlong('100,100');
        $this->assertEquals('100,100', $this->page->getLatlong());
    }

    public function testIsDraft(): void
    {
        $this->page->setStatus(EditorialStatus::DRAFT);
        $this->assertTrue($this->page->isDraft());
        $this->page->setStatus(EditorialStatus::PUBLISHED);
        $this->assertFalse($this->page->isDraft());
    }

    public function testSetAndGetContent(): void
    {
        $this->page->setContent('test');
        $this->assertEquals('test', $this->page->getContent());
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testSetAndGetTimezone(): void
    {
        $this->page->setTimezone('Europe/London');
        $this->assertEquals('Europe/London', $this->page->getTimezone());
        $this->expectException(InvalidTimezoneException::class);
        $this->page->setTimezone('test');
    }

    public function testSetAndGetFeatureImage(): void
    {
        $image = new Image();
        $this->page->setFeatureImage($image);
        $this->assertEquals($image, $this->page->getFeatureImage());
        $this->page->setFeatureImage(null);
        $this->assertEquals(null, $this->page->getFeatureImage());
    }

    public function testSetAndGetPassword(): void
    {
        $this->page->setPassword('test');
        $this->assertEquals('test', $this->page->getPassword());
    }

    public function testSetAndGetTitle(): void
    {
        $this->page->setTitle('test');
        $this->assertEquals('test', $this->page->getTitle());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $currentTime = new DateTimeImmutable('now');
        $this->page->setCreatedAt($currentTime);
        $this->assertEquals($currentTime, $this->page->getCreatedAt());
    }

    /**
     * @throws Exception
     */
    public function testIsScheduledPage(): void
    {
        $currentTime = new DateTimeImmutable('yesterday');
        $this->page->setPostDate($currentTime);
        $this->page->setStatus(EditorialStatus::PUBLISHED);
        $this->assertFalse($this->page->isScheduledPage());
        $currentTime = new DateTimeImmutable('tomorrow');
        $this->page->setPostDate($currentTime);
        $this->assertTrue($this->page->isScheduledPage());
    }

    public function testSetAndGetVisibility(): void
    {
        $this->page->setVisible(false);
        $this->assertEquals(false, $this->page->isVisible());
        $this->page->setVisible(true);
        $this->assertEquals(true, $this->page->isVisible());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $currentTime = new DateTimeImmutable('now');
        $this->page->setUpdatedAt($currentTime);
        $this->assertEquals($currentTime, $this->page->getUpdatedAt());
    }

    public function testIsAllowComments(): void
    {
        $this->page->setAllowComments();
        $this->assertTrue($this->page->isAllowComments());
    }

    /**
     * @throws Exception
     */
    public function testSetAndGetType(): void
    {
        $this->page->setType(Page::TYPE_PAGE);
        $this->assertEquals(Page::TYPE_PAGE, $this->page->getType());
        $this->page->setType(Page::TYPE_POST);
        $this->assertEquals(Page::TYPE_POST, $this->page->getType());
        $this->expectException(Exception::class);
        $this->page->setType('test');
    }

    public function testSetAndGetSharingMessage(): void
    {
        $this->page->setSharingMessage('test');
        $this->assertEquals('test', $this->page->getSharingMessage());
    }

    public function testSetAndGetSubTitle(): void
    {
        $this->page->setSubTitle('test');
        $this->assertEquals('test', $this->page->getSubTitle());
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->page->setId($uuid);
        $this->assertEquals($uuid, $this->page->getId());
    }

    public function testSetAndGetStatus(): void
    {
        $this->page->setStatus(EditorialStatus::DRAFT);
        $this->assertEquals(EditorialStatus::DRAFT, $this->page->getStatus());
        $this->page->setStatus(EditorialStatus::PUBLISHED);
        $this->assertEquals(EditorialStatus::PUBLISHED, $this->page->getStatus());
    }

    public function testSetAndGetFeatureSnippet(): void
    {
        $this->page->setFeatureSnippet('test');
        $this->assertEquals('test', $this->page->getFeatureSnippet());
    }

    public function testSetAndGetPostDate(): void
    {
        $currentTime = new DateTimeImmutable('now');
        $this->page->setPostDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getPostDate());
    }

    public function testSetAndGetAuthor(): void
    {
        $this->page->setAuthor(new User('test'));
        $this->assertInstanceOf(User::class, $this->page->getAuthor());
        $this->assertEquals('test', $this->page->getAuthor()->getUsername());
    }

    /**
     * @throws Exception
     */
    public function testAddAndGetUrls(): void
    {
        $this->assertNull($this->page->getUrl());
        $this->page->addUrl(new Url($this->page, 'test', true));
        $this->assertNotEmpty($this->page->getUrls());
        $this->assertInstanceOf('\Inachis\Entity\Content\Url', $this->page->getUrl());
        $this->expectException(InvalidArgumentException::class);
        $this->page->getUrl(100);
    }

    public function testAddAndGetCategories(): void
    {
        $this->page->addCategory(new Category('test-category'));
        $this->assertNotEmpty($this->page->getCategories());
        $this->page->removeCategories();
        $this->assertEmpty($this->page->getCategories());
    }

    public function testAddAndGetTags(): void
    {
        $this->page->addTag(new Tag('test-tag'));
        $this->assertNotEmpty($this->page->getTags());
        $this->page->removeTags();
        $this->assertEmpty($this->page->getTags());
    }

    public function testGetPostDateAsLink(): void
    {
        $this->page->setPostDate(new DateTimeImmutable('1970-01-01'));
        $this->assertEquals('1970/01/01', $this->page->getPostDateAsLink());
    }

    public function testHasHotlinkedImages(): void
    {
        $this->assertFalse($this->page->hasHotlinkedImages());
        $this->page->setContent('![test](/imgs/test.png)');
        $this->assertFalse($this->page->hasHotlinkedImages());
        $this->page->setContent('[test](https://example.com/imgs/test.png)');
        $this->assertFalse($this->page->hasHotlinkedImages());
        $this->page->setContent('![test](https://example.com/imgs/test.png)');
        $this->assertTrue($this->page->hasHotlinkedImages());
    }

    public function testIsExportable(): void
    {
        $this->assertTrue($this->page->isExportable());
    }

    public function testGetName(): void
    {
        $this->assertEquals('Pages and Posts', $this->page->getName());
    }

    public function testSetAndGetLanguage(): void
    {
        $this->page->setLanguage('en');
        $this->assertEquals('en', $this->page->getLanguage());
        $this->page->setLanguage('cn');
        $this->assertEquals('cn', $this->page->getLanguage());
    }

    public function testSetAndGetNoindex(): void
    {
        $this->page->setNoindex(true);
        $this->assertTrue($this->page->getNoindex());
        $this->page->setNoindex(false);
        $this->assertFalse($this->page->getNoindex());
    }

    public function testSetAndGetNofollow(): void
    {
        $this->page->setNofollow(true);
        $this->assertTrue($this->page->getNofollow());
        $this->page->setNofollow(false);
        $this->assertFalse($this->page->getNofollow());
    }
}
