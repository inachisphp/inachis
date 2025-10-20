<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class MockUser implements UserInterface {
    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * @return void
     */
    public function eraseCredentials(): void { }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return '';
    }
}

class UserCheckerTest extends TestCase
{
    protected UserChecker $userChecker;

    public function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    public function testCheckPreAuth(): void
    {
        $user = new User();
        $this->assertEmpty($this->userChecker->checkPreAuth($user));
    }

    public function testCheckPreAuthNotAUser(): void
    {
        $user = new MockUser();
        $this->assertEmpty($this->userChecker->checkPreAuth($user));
    }

    public function testCheckPreAuthNotEnabled(): void
    {
        $user = (new User())->setActive(false);
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your account has been disabled.');
        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthRemoved(): void
    {
        $user = (new User())->setActive(true)->setRemoved(true);
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Invalid credentials.');
        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPostAuth(): void
    {
        $user = new User();
        $this->assertEmpty($this->userChecker->checkPostAuth($user));
    }
}