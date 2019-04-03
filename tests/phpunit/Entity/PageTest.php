<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Page;
use App\Entity\Url;
use App\Entity\User;
use App\Exception\InvalidTimezoneException;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    protected $page;

    public function setUp()
    {
        $this->page = new Page();
    }

    public function testSetAndGetLatlong()
    {
        $this->page->setLatlong('100,100');
        $this->assertEquals('100,100', $this->page->getLatlong());
    }

    public function testIsDraft()
    {
        $this->page->setStatus(Page::DRAFT);
        $this->assertTrue($this->page->isDraft());
        $this->page->setStatus(Page::PUBLISHED);
        $this->assertFalse($this->page->isDraft());
    }

    public function testSetAndGetContent()
    {
        $this->page->setContent('test');
        $this->assertEquals('test', $this->page->getContent());
    }

    public function testSetAndGetTimezone()
    {
        $this->page->setTimezone('Europe/London');
        $this->assertEquals('Europe/London', $this->page->getTimezone());
        $this->expectException(InvalidTimezoneException::class);
        $this->page->setTimezone('test');
    }

    public function testSetAndGetFeatureImage()
    {
        $this->page->setFeatureImage('test');
        $this->assertEquals('test', $this->page->getFeatureImage());
    }

    public function testSetAndGetPassword()
    {
        $this->page->setPassword('test');
        $this->assertEquals('test', $this->page->getPassword());
    }

    public function testIsValidVisibility()
    {
        $this->assertTrue($this->page->isValidVisibility(Page::VIS_PRIVATE));
        $this->assertTrue($this->page->isValidVisibility(Page::VIS_PUBLIC));
        $this->assertFalse($this->page->isValidVisibility('test'));
    }

    public function testSetAndGetTitle()
    {
        $this->page->setTitle('test');
        $this->assertEquals('test', $this->page->getTitle());
    }

    public function testSetAndGetCreateDate()
    {
        $currentTime = new \DateTime('now');
        $this->page->setCreateDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getCreateDate());
    }

    public function testIsScheduledPage()
    {
        $currentTime = new \DateTime('yesterday');
        $this->page->setPostDate($currentTime);
        $this->assertFalse($this->page->isScheduledPage());
        $currentTime = new \DateTime('tomorrow');
        $this->page->setPostDate($currentTime);
        $this->assertTrue($this->page->isScheduledPage());
    }

    public function testSetAndGetVisibility()
    {
        $this->page->setVisibility(Page::VIS_PRIVATE);
        $this->assertEquals(Page::VIS_PRIVATE, $this->page->getVisibility());
    }

    public function testSetAndGetModDate()
    {
        $currentTime = new \DateTime('now');
        $this->page->setModDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getModDate());
    }

    public function testIsAllowComments()
    {
        $this->page->setAllowComments(true);
        $this->assertTrue($this->page->isAllowComments());
    }

    public function testSetAndGetType()
    {
        $this->page->setType(Page::TYPE_PAGE);
        $this->assertEquals(Page::TYPE_PAGE, $this->page->getType());
        $this->page->setType(Page::TYPE_POST);
        $this->assertEquals(Page::TYPE_POST, $this->page->getType());
    }

    public function testSetAndGetSharingMessage()
    {
        $this->page->setSharingMessage('test');
        $this->assertEquals('test', $this->page->getSharingMessage());
    }

    public function testSetAndGetSubTitle()
    {
        $this->page->setSubTitle('test');
        $this->assertEquals('test', $this->page->getSubTitle());
    }

    public function testSetAndGetId()
    {
        $this->page->setId('test');
        $this->assertEquals('test', $this->page->getId());
    }

    public function testSetAndGetStatus()
    {
        $this->page->setStatus(Page::DRAFT);
        $this->assertEquals(Page::DRAFT, $this->page->getStatus());
    }

    public function testSetAndGetFeatureSnippet()
    {
            $this->page->setFeatureSnippet('test');
            $this->assertEquals('test', $this->page->getFeatureSnippet());
    }

    public function testIsValidStatus()
    {
        $this->assertFalse($this->page->isValidStatus('test'));
        $this->assertTrue($this->page->isValidStatus(Page::DRAFT));
        $this->assertTrue($this->page->isValidStatus(Page::PUBLISHED));
    }

    public function testSetAndGetPostDate()
    {
        $currentTime = new \DateTime('now');
        $this->page->setPostDate($currentTime);
        $this->assertEquals($currentTime, $this->page->getPostDate());
    }

    public function testSetAndGetAuthor()
    {
        $this->page->setAuthor(new User('test'));
        $this->assertInstanceOf(User::class, $this->page->getAuthor());
        $this->assertEquals('test', $this->page->getAuthor()->getUsername());
    }

    public function testAddAndGetUrls()
    {
        $this->page->addUrl(new Url($this->page, 'test', true));
        $this->assertNotEmpty($this->page->getUrls());
    }

}
