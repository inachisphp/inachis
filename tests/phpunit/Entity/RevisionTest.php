<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use DateTimeImmutable;
use Exception;
use Inachis\Entity\Revision;
use Inachis\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RevisionTest extends TestCase
{
    protected Revision $revision;

    public function setUp(): void
    {
        $this->revision = new Revision();
        parent::setUp();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->revision->setId($uuid);
        $this->assertEquals($uuid, $this->revision->getId());
    }

    public function testGetAndSetPageId(): void
    {
        $this->revision->setPageId('test');
        $this->assertEquals('test', $this->revision->getPageId());
    }

    /**
     * @throws Exception
     */
    public function testGetAndSetVersionNumber(): void
    {
        $this->revision->setVersionNumber(223);
        $this->assertEquals(223, $this->revision->getVersionNumber());
        $this->expectException(Exception::class);
        $this->revision->setVersionNumber(-1);
    }

    public function testGetAndSetModDate(): void
    {
        $testDate = new DateTimeImmutable();
        $this->revision->setModDate($testDate);
        $this->assertEquals($testDate, $this->revision->getModDate());
    }

    public function testGetAndSetUser(): void
    {
        $testUser = new User();
        $this->revision->setUser($testUser);
        $this->assertEquals($testUser, $this->revision->getUser());
    }

    public function testGetAndSetAction(): void
    {
        $this->revision->setAction('Updated content');
        $this->assertEquals('Updated content', $this->revision->getAction());
    }

    public function testGetAndSetTitle(): void
    {
        $this->revision->setTitle('Test');
        $this->assertEquals('Test', $this->revision->getTitle());
    }

    public function testGetAndSetSubTitle(): void
    {
        $this->revision->setSubTitle('Test');
        $this->assertEquals('Test', $this->revision->getSubTitle());
    }

    public function testGetAndSetContent(): void
    {
        $this->revision->setContent('Test');
        $this->assertEquals('Test', $this->revision->getContent());
    }
}
