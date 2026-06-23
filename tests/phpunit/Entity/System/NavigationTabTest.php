<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\System;

use Inachis\Entity\System\NavigationTab;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(NavigationTab::class)]
class NavigationTabTest extends TestCase
{
    private NavigationTab $tab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tab = new NavigationTab();
    }

    #[Test]
    public function defaultConstructorProducesExpectedValues(): void
    {
        self::assertNull($this->tab->getId());
        self::assertSame(0, $this->tab->getPosition());
        self::assertTrue($this->tab->isActive());
        self::assertFalse($this->tab->isSystem());
    }

    #[Test]
    public function setAndGetId(): void
    {
        $uuid = Uuid::uuid4();
        $result = $this->tab->setId($uuid);
        self::assertSame($uuid, $this->tab->getId());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setAndGetTitle(): void
    {
        $result = $this->tab->setTitle('Home');
        self::assertSame('Home', $this->tab->getTitle());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setAndGetUrlForNonSystemTab(): void
    {
        $result = $this->tab->setUrl('/home');
        self::assertSame('/home', $this->tab->getUrl());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setUrlDoesNothingForSystemTab(): void
    {
        $this->tab->setIsSystem(true);
        $this->tab->setTitle('Dashboard');
        // Let's set URL initially via reflection or just verify it does not change.
        // Wait, url is uninitialized in PHP if not set. So if we try to setUrl on a system tab when url is uninitialized,
        // it doesn't set it. Let's initialize URL first by setting isSystem to false, setting url, and then setting isSystem to true.
        $this->tab->setIsSystem(false);
        $this->tab->setUrl('/admin/dashboard');
        
        $this->tab->setIsSystem(true);
        $result = $this->tab->setUrl('/changed-url');
        
        self::assertSame('/admin/dashboard', $this->tab->getUrl());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setAndGetPosition(): void
    {
        $result = $this->tab->setPosition(5);
        self::assertSame(5, $this->tab->getPosition());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setAndGetIsActive(): void
    {
        $result = $this->tab->setIsActive(false);
        self::assertFalse($this->tab->isActive());
        self::assertSame($this->tab, $result);
    }

    #[Test]
    public function setAndGetIsSystem(): void
    {
        $result = $this->tab->setIsSystem(true);
        self::assertTrue($this->tab->isSystem());
        self::assertSame($this->tab, $result);
    }
}
