<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Inachis\Controller\Page\Admin\UserTrustedDevicesController;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserTrustedDevice;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class UserTrustedDevicesControllerTest extends InachisControllerTestCase
{
    protected UserTrustedDevicesController $controller;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->getMockBuilder(UserTrustedDevicesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'getCurrentUser',
                'redirectToRoute',
                'createAccessDeniedException',
            ])
            ->getMock();
    }

    /**
     * @throws Exception
     */
    public function testRenameTrustedDevice(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $device = new UserTrustedDevice();
        $device->setUser($user);
        $device->setDisplayName('Old name');

        $request = new Request([], [
            'display_name' => 'New device name',
        ]);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Trusted device renamed.');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->controller->renameTrustedDevice(
            $request,
            $device,
            $this->createStub(TrustedDeviceManager::class),
        );

        $this->assertSame('New device name', $device->getDisplayName());
        $this->assertSame(
            '/incp/admin/test-user',
            $result->getTargetUrl(),
        );
    }

    /**
     * @throws Exception
     */
    public function testRenameTrustedDeviceEmptyName(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $device = new UserTrustedDevice();
        $device->setUser($user);

        $request = new Request([], [
            'display_name' => '   ',
        ]);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('error', 'Device name cannot be empty.');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->controller->renameTrustedDevice(
            $request,
            $device,
            $this->createStub(TrustedDeviceManager::class),
        );

        $this->assertSame(
            '/incp/admin/test-user',
            $result->getTargetUrl(),
        );
    }

    /**
     * @throws Exception
     */
    public function testRenameTrustedDeviceTruncatesLongName(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $device = new UserTrustedDevice();
        $device->setUser($user);

        $displayName = str_repeat('A', 150);

        $request = new Request([], [
            'display_name' => $displayName,
        ]);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Trusted device renamed.');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->controller->renameTrustedDevice(
            $request,
            $device,
            $this->createStub(TrustedDeviceManager::class),
        );

        $this->assertSame(
            100,
            mb_strlen($device->getDisplayName()),
        );
    }

    /**
     * @throws Exception
     */
    public function testRenameTrustedDeviceDeniedForAnotherUser(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $otherUser = new User();
        $otherUser->setUsername('other-user');

        $device = new UserTrustedDevice();
        $device->setUser($otherUser);

        $this->controller->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('createAccessDeniedException')
            ->willReturn(new AccessDeniedException());

        $this->expectException(AccessDeniedException::class);

        $this->controller->renameTrustedDevice(
            new Request(),
            $device,
            $this->createStub(TrustedDeviceManager::class),
        );
    }

    /**
     * @throws Exception
     */
    public function testRemoveTrustedDevice(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $device = new UserTrustedDevice();
        $device->setUser($user);

        $request = new Request();

        $trustedDeviceManager = $this->createMock(
            TrustedDeviceManager::class,
        );

        $trustedDeviceManager->expects($this->once())
            ->method('getCurrentTrustedDevice')
            ->with($user, $request)
            ->willReturn(null);

        $trustedDeviceManager->expects($this->once())
            ->method('remove')
            ->with($device);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Trusted device removed.');

        $result = $this->controller->removeTrustedDevice(
            $request,
            $device,
            $trustedDeviceManager,
        );

        $this->assertSame(
            '/incp/admin/test-user',
            $result->getTargetUrl(),
        );
        $this->assertSame([], $result->headers->getCookies());
    }

    /**
     * @throws Exception
     */
    public function testRemoveCurrentTrustedDeviceClearsCookie(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $deviceId = Uuid::uuid4();

        $device = $this->createMock(UserTrustedDevice::class);
        $device->method('getUser')->willReturn($user);
        $device->method('getId')->willReturn($deviceId);

        $request = new Request();

        $clearCookie = Cookie::create(
            'trusted_device',
            '',
            1,
            '/',
            null,
            true,
            true,
        );

        $trustedDeviceManager = $this->createMock(
            TrustedDeviceManager::class,
        );

        $trustedDeviceManager->expects($this->once())
            ->method('getCurrentTrustedDevice')
            ->with($user, $request)
            ->willReturn($device);

        $trustedDeviceManager->expects($this->once())
            ->method('remove')
            ->with($device);

        $trustedDeviceManager->expects($this->once())
            ->method('clearCookie')
            ->willReturn($clearCookie);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Trusted device removed.');

        $result = $this->controller->removeTrustedDevice(
            $request,
            $device,
            $trustedDeviceManager,
        );

        $this->assertSame(
            '/incp/admin/test-user',
            $result->getTargetUrl(),
        );

        $cookies = $result->headers->getCookies();

        $this->assertCount(1, $cookies);
        $this->assertSame(
            'trusted_device',
            $cookies[0]->getName(),
        );
    }

    /**
     * @throws Exception
     */
    public function testRemoveTrustedDeviceDeniedForAnotherUser(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $otherUser = new User();
        $otherUser->setUsername('other-user');

        $device = new UserTrustedDevice();
        $device->setUser($otherUser);

        $this->controller->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('createAccessDeniedException')
            ->willReturn(new AccessDeniedException());

        $this->expectException(AccessDeniedException::class);

        $this->controller->removeTrustedDevice(
            new Request(),
            $device,
            $this->createStub(TrustedDeviceManager::class),
        );
    }

    /**
     * @throws Exception
     */
    public function testRemoveAllTrustedDevices(): void
    {
        $user = new User();
        $user->setUsername('test-user');

        $clearCookie = Cookie::create(
            'trusted_device',
            '',
            1,
            '/',
            null,
            true,
            true,
        );

        $trustedDeviceManager = $this->createMock(
            TrustedDeviceManager::class,
        );

        $trustedDeviceManager->expects($this->once())
            ->method('removeAll')
            ->with($user);

        $trustedDeviceManager->expects($this->once())
            ->method('clearCookie')
            ->willReturn($clearCookie);

        $this->controller->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_edit', [
                'id' => 'test-user',
            ])
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'success',
                'All trusted devices have been removed.',
            );

        $result = $this->controller->removeAllTrustedDevices(
            $trustedDeviceManager,
        );

        $this->assertSame(
            '/incp/admin/test-user',
            $result->getTargetUrl(),
        );

        $cookies = $result->headers->getCookies();

        $this->assertCount(1, $cookies);
        $this->assertSame(
            'trusted_device',
            $cookies[0]->getName(),
        );
    }
}
