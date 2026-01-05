<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\Category;
use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Inachis\Entity\Tag;
use Inachis\Entity\Url;
use Inachis\Entity\User;
use Inachis\Exception\InvalidTimezoneException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use InvalidArgumentException;
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
        $this->page->setStatus();
        $this->assertTrue($this->page->isDraft());
        $this->page->setStatus(Page::PUBLISHED);
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

    public function testSetAndGetCreateDate(): void
    {
        $currentTime = new DateTime('now');
        $this->page->setCreateDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getCreateDate());
    }

    /**
     * @throws Exception
     */
    public function testIsScheduledPage(): void
    {
        $currentTime = new DateTime('yesterday');
        $this->page->setPostDate($currentTime);
        $this->page->setStatus(Page::PUBLISHED);
        $this->assertFalse($this->page->isScheduledPage());
        $currentTime = new DateTime('tomorrow');
        $this->page->setPostDate($currentTime);
        $this->assertTrue($this->page->isScheduledPage());
    }

    public function testSetAndGetVisibility(): void
    {
        $this->page->setVisibility();
        $this->assertEquals(Page::PRIVATE, $this->page->getVisibility());
        $this->page->setVisibility(Page::PUBLIC);
        $this->assertEquals(Page::PUBLIC, $this->page->getVisibility());
    }

    public function testSetAndGetModDate(): void
    {
        $currentTime = new DateTime('now');
        $this->page->setModDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getModDate());
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
        $this->page->setStatus();
        $this->assertEquals(Page::DRAFT, $this->page->getStatus());
        $this->page->setStatus(Page::PUBLISHED);
        $this->assertEquals(Page::PUBLISHED, $this->page->getStatus());
    }

    public function testSetAndGetFeatureSnippet(): void
    {
        $this->page->setFeatureSnippet('test');
        $this->assertEquals('test', $this->page->getFeatureSnippet());
    }

    public function testIsValidStatus(): void
    {
        $this->assertFalse($this->page->isValidStatus('test'));
        $this->assertTrue($this->page->isValidStatus(Page::DRAFT));
        $this->assertTrue($this->page->isValidStatus(Page::PUBLISHED));
    }

    public function testSetAndGetPostDate(): void
    {
        $currentTime = new DateTime('now');
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
        $this->assertInstanceOf('\Inachis\Entity\Url', $this->page->getUrl());
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
        $this->page->setPostDate(new DateTime('1970-01-01'));
        $this->assertEquals('1970/01/01', $this->page->getPostDateAsLink());
        $this->page->setPostDate();
        $this->assertEquals('', $this->page->getPostDateAsLink());
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

    public function testSetAndGetSeries(): void
    {
        $this->assertEmpty($this->page->getSeries());
        $series = new Series();
        $collection = new ArrayCollection();
        $collection->add($series);
        $this->page->setSeries($collection);
        $this->assertTrue($this->page->getSeries()->contains($series));
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
