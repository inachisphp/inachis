<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Provider;

use Inachis\Entity\User\User;
use Inachis\Entity\User\UserPreference;
use Inachis\Provider\TimezoneProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TimezoneProviderTest extends TestCase
{
    #[Test]
    public function itReturnsTheConfiguredDefaultTimezone(): void
    {
        $provider = new TimezoneProvider('Europe/London');

        self::assertSame('Europe/London', $provider->getDefault());
    }

    #[Test]
    public function itReturnsTheDefaultTimezoneWhenNoUserIsProvided(): void
    {
        $provider = new TimezoneProvider('Europe/London');

        self::assertSame('Europe/London', $provider->getForUser(null));
    }

    #[Test]
    public function itReturnsTheDefaultTimezoneWhenUserHasNoPreferences(): void
    {
        $provider = new TimezoneProvider('Europe/London');
        $user = new User('test-user');

        self::assertNull($user->getPreferences());

        self::assertSame(
            'Europe/London',
            $provider->getForUser($user),
        );
    }

    #[Test]
    public function itReturnsTheUsersTimezoneWhenPreferencesAreConfigured(): void
    {
        $provider = new TimezoneProvider('Europe/London');
        $user = new User('test-user');
        $preferences = new UserPreference($user);

        $preferences->setTimezone('America/New_York');
        $user->setPreferences($preferences);

        self::assertSame(
            'America/New_York',
            $provider->getForUser($user),
        );
    }
}
