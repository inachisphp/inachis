<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\Page;
use Inachis\Entity\Url;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UrlTest extends TestCase
{
    protected ?Url $url;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->url = new Url(new Page());
        parent::setUp();
    }

    /**
     *
     */
    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->url->setId($uuid);
        $this->assertEquals($uuid, $this->url->getId());
    }

    /**
     *
     */
    public function testSetAndGetLink(): void
    {
        $this->url->setLink('test');
        $this->assertEquals('test', $this->url->getLink());
    }

    /**
     *
     */
    public function testSetAndGetLinkCanonical(): void
    {
        $this->url->setLink('test');
        $this->assertEquals(md5('test'), $this->url->getLinkCanonical());
    }

    /**
     * @throws Exception
     */
    public function testSetModDateToNow(): void
    {
        $yesterdayDateTime = new DateTime('yesterday');
        $this->url->setModDate($yesterdayDateTime);
        $this->url->setModDateToNow();
        $this->assertEquals(
            (new DateTime('now'))->format('Ymd'),
            $this->url->getModDate()->format('Ymd')
        );
    }

    /**
     *
     */
    public function testValidateURL(): void
    {
        $this->url->setLink('test-link');
        $this->assertTrue($this->url->validateURL());
        $this->url->setLink('test\'s-link');
        $this->assertFalse($this->url->validateURL());
    }

    public function testGetCreateDate(): void
    {
        $this->assertGreaterThan(0, $this->url->getCreateDate()->getTimestamp());
    }

    public function testGetContent(): void
    {
        $this->assertInstanceOf(Page::class, $this->url->getContent());
    }

    public function testIsDefault(): void
    {
        $this->assertTrue($this->url->isDefault());
        $this->url->setDefault(false);
        $this->assertFalse($this->url->isDefault());
    }
}
