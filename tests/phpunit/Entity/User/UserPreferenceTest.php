<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\User;
use Inachis\Entity\User\UserPreference;
use Inachis\Exception\InvalidTimezoneException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(UserPreference::class)]
class UserPreferenceTest extends TestCase
{
    private User $user;
    private UserPreference $preferences;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
        $this->preferences = new UserPreference($this->user);
    }

    #[Test]
    public function defaultConstructorProducesExpectedValues(): void
    {
        self::assertSame($this->user, $this->preferences->getUser());
        self::assertSame('light', $this->preferences->getTheme());
        self::assertFalse($this->preferences->getHighContrast());
        self::assertSame('default', $this->preferences->getFontSize());
        self::assertSame('sans', $this->preferences->getFontFamily());
        self::assertSame('default', $this->preferences->getLineHeight());
        self::assertSame('en', $this->preferences->getLocale());
        self::assertSame('UTC', $this->preferences->getTimezone());
        self::assertSame('#099bdd', $this->preferences->getColor());
    }

    #[Test]
    public function setAndGetId(): void
    {
        $uuid = Uuid::uuid4();
        $result = $this->preferences->setId($uuid);
        self::assertSame($uuid, $this->preferences->getId());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetUser(): void
    {
        $newUser = new User();
        $result = $this->preferences->setUser($newUser);
        self::assertSame($newUser, $this->preferences->getUser());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetTheme(): void
    {
        $result = $this->preferences->setTheme('dark');
        self::assertSame('dark', $this->preferences->getTheme());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetHighContrast(): void
    {
        $result = $this->preferences->setHighContrast(true);
        self::assertTrue($this->preferences->getHighContrast());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetFontSize(): void
    {
        $result = $this->preferences->setFontSize('larger');
        self::assertSame('larger', $this->preferences->getFontSize());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetFontFamily(): void
    {
        $result = $this->preferences->setFontFamily('mono');
        self::assertSame('mono', $this->preferences->getFontFamily());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetLineHeight(): void
    {
        $result = $this->preferences->setLineHeight('spacious');
        self::assertSame('spacious', $this->preferences->getLineHeight());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetLocale(): void
    {
        $result = $this->preferences->setLocale('fr');
        self::assertSame('fr', $this->preferences->getLocale());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setAndGetTimezoneValid(): void
    {
        $result = $this->preferences->setTimezone('Europe/London');
        self::assertSame('Europe/London', $this->preferences->getTimezone());
        self::assertSame($this->preferences, $result);
    }

    #[Test]
    public function setTimezoneThrowsExceptionOnInvalidTimezone(): void
    {
        $this->expectException(InvalidTimezoneException::class);
        $this->preferences->setTimezone('Invalid/Timezone');
    }

    #[Test]
    public function setAndGetColor(): void
    {
        $result = $this->preferences->setColor('#ffffff');
        self::assertSame('#ffffff', $this->preferences->getColor());
        self::assertSame($this->preferences, $result);
    }
}
