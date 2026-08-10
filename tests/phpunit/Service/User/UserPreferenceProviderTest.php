<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\User;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserPreference;
use Inachis\Service\User\UserPreferenceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserPreferenceProviderTest extends TestCase
{
    private Security $security;
    private RequestStack $requestStack;
    private SessionInterface&MockObject $session;
    private EntityManagerInterface&MockObject $entityManager;
    private UserPreferenceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->createStub(Security::class);
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->provider = new UserPreferenceProvider(
            $this->security,
            $this->requestStack,
            $this->entityManager,
        );
    }

    public function testGetReturnsNullWhenUserIsNotAuthenticated(): void
    {
        $this->security->method('getUser')->willReturn(null);

        $this->assertNull($this->provider->get());
    }

    public function testGetReturnsExistingPreferencesAndSetsSession(): void
    {
        $user = $this->createUser();
        $preferences = $this->createUserPreference($user);

        $this->setUserPreferences($user, $preferences);
        $this->security->method('getUser')->willReturn($user);

        $this->session->expects($this->once())
            ->method('set')
            ->with('user_preferences', $preferences);

        $this->entityManager->expects($this->never())->method('persist');

        $result = $this->provider->get();

        $this->assertSame($preferences, $result);
    }

    public function testGetCreatesNewPreferencesWhenUserHasNone(): void
    {
        $user = $this->createUser();
        $this->setUserPreferences($user, null);

        $this->security->method('getUser')->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(UserPreference::class));

        $this->session->expects($this->once())
            ->method('set')
            ->with('user_preferences', $this->isInstanceOf(UserPreference::class));

        $result = $this->provider->get();

        $this->assertInstanceOf(UserPreference::class, $result);
    }

    public function testSavePersistsFlushesAndUpdatesSession(): void
    {
        $user = $this->createUser();
        $preferences = $this->createUserPreference($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($preferences);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->session->expects($this->once())
            ->method('set')
            ->with('user_preferences', $preferences);

        $this->provider->save($preferences);
    }

    private function createUser(): User
    {
        $reflection = new \ReflectionClass(User::class);

        if (!$reflection->isFinal()) {
            return $this->createMock(User::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }

    private function createUserPreference(User $user): UserPreference
    {
        $reflection = new \ReflectionClass(UserPreference::class);

        if (!$reflection->isFinal()) {
            return $this->createMock(UserPreference::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }

    private function setUserPreferences(User $user, ?UserPreference $preferences): void
    {
        if ($user instanceof MockObject) {
            $user->method('getPreferences')->willReturn($preferences);
            return;
        }

        $reflection = new \ReflectionClass($user);
        foreach (['preferences', 'userPreferences'] as $propName) {
            if ($reflection->hasProperty($propName)) {
                $prop = $reflection->getProperty($propName);
                $prop->setValue($user, $preferences);
            }
        }
    }
}
