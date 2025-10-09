<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Page;
use App\Entity\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    protected ?Url $url;

    /**
     * @throws \Exception
     */
    public function setUp() : void
    {
        $this->url = new Url(new Page());

        parent::setUp();
    }

    /**
     *
     */
    public function testSetAndGetId()
    {
        $this->url->setId('test');
        $this->assertEquals('test', $this->url->getId());
    }

    /**
     *
     */
    public function testSetAndGetLink()
    {
        $this->url->setLink('test');
        $this->assertEquals('test', $this->url->getLink());
    }

    /**
     *
     */
    public function testSetAndGetLinkCanonical()
    {
        $this->url->setLink('test');
        $this->assertEquals(md5('test'), $this->url->getLinkCanonical());
    }

    /**
     * @throws \Exception
     */
    public function testSetModDateToNow()
    {
        $yesterdayDateTime = new \DateTime('yesterday');
        $this->url->setModDate($yesterdayDateTime);
        $this->url->setModDateToNow();
        $this->assertEquals(
            (new \DateTime('now'))->format('Ymd'),
            $this->url->getModDate()->format('Ymd')
        );
    }

    /**
     *
     */
    public function testValidateURL()
    {
        $this->url->setLink('test-link');
        $this->assertTrue($this->url->validateURL());
        $this->url->setLink('test\'s-link');
        $this->assertFalse($this->url->validateURL());
    }

    public function testGetCreateDate()
    {
        $this->assertGreaterThan(0, $this->url->getCreateDate()->getTimestamp());
    }

    public function testGetContent()
    {
        $this->assertInstanceOf(Page::class, $this->url->getContent());
    }

    public function testIsDefault()
    {
        $this->assertTrue($this->url->isDefault());
        $this->url->setDefault(false);
        $this->assertFalse($this->url->isDefault());
    }
}
