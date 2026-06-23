<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\System;

use Inachis\Entity\System\Setting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(Setting::class)]
class SettingTest extends TestCase
{
    private Setting $setting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setting = new Setting();
    }

    #[Test]
    public function defaultStateProducesExpectedValues(): void
    {
        self::assertNull($this->setting->getId());
        self::assertNull($this->setting->getValue());
        self::assertNull($this->setting->getEncryptedValue());
        self::assertNull($this->setting->getEncryptedKey());
        self::assertSame(1, $this->setting->getKeyVersion());
    }

    #[Test]
    public function setAndGetId(): void
    {
        $uuid = Uuid::uuid4();
        $result = $this->setting->setId($uuid);
        self::assertSame($uuid, $this->setting->getId());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetName(): void
    {
        $result = $this->setting->setName('site_name');
        self::assertSame('site_name', $this->setting->getName());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetValue(): void
    {
        $result = $this->setting->setValue('My Awesome Site');
        self::assertSame('My Awesome Site', $this->setting->getValue());
        self::assertInstanceOf(\DateTimeImmutable::class, $this->setting->getUpdatedAt());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetEncryptedValue(): void
    {
        $result = $this->setting->setEncryptedValue('encrypted-string-here');
        self::assertSame('encrypted-string-here', $this->setting->getEncryptedValue());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetEncryptedKey(): void
    {
        $result = $this->setting->setEncryptedKey('encrypted-key-here');
        self::assertSame('encrypted-key-here', $this->setting->getEncryptedKey());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetKeyVersion(): void
    {
        $result = $this->setting->setKeyVersion(2);
        self::assertSame(2, $this->setting->getKeyVersion());
        self::assertSame($this->setting, $result);
    }

    #[Test]
    public function setAndGetUpdatedAt(): void
    {
        $time = new \DateTimeImmutable('2025-01-01 12:00:00');
        $result = $this->setting->setUpdatedAt($time);
        self::assertSame($time, $this->setting->getUpdatedAt());
        self::assertSame($this->setting, $result);
    }
}
