<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Twig;

use Inachis\Entity\User\UserPreference;
use Inachis\Service\User\UserPreferenceProvider;
use Inachis\Twig\UserPreferencesExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPreferencesExtensionTest extends TestCase
{
    public function testGetGlobalsReturnsUserPreferencesWhenUserIsSignedInAndHasRole(): void
    {
        $userPreference = $this->createStub(UserPreference::class);

        $user = $this->createStub(UserInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturnMap([
            ['ROLE_USER', true],
        ]);

        $preferenceProvider = $this->createStub(UserPreferenceProvider::class);
        $preferenceProvider->method('get')->willReturn($userPreference);

        $extension = new UserPreferencesExtension($preferenceProvider, $security);

        $globals = $extension->getGlobals();

        $this->assertSame([
            'userPreference' => $userPreference,
        ], $globals);
    }

    public function testGetGlobalsReturnsEmptyArrayWhenUserIsNotSignedIn(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $preferenceProvider = $this->createStub(UserPreferenceProvider::class);

        $extension = new UserPreferencesExtension($preferenceProvider, $security);

        $this->assertSame([], $extension->getGlobals());
    }

    public function testGetGlobalsReturnsEmptyArrayWhenUserIsSignedInButLacksRoleUser(): void
    {
        $user = $this->createStub(UserInterface::class);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturnMap([
            ['ROLE_USER', false],
        ]);

        $preferenceProvider = $this->createStub(UserPreferenceProvider::class);

        $extension = new UserPreferencesExtension($preferenceProvider, $security);

        $this->assertSame([], $extension->getGlobals());
    }
}
