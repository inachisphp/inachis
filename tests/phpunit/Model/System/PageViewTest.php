<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PageViewTest extends TestCase
{
    #[Test]
    public function itCreatesAPageViewWithDefaultValues(): void
    {
        $settings = new \ReflectionClass(SiteSettings::class)
            ->newInstanceWithoutConstructor();

        $page = new \ReflectionClass(PageMetadata::class)
            ->newInstanceWithoutConstructor();

        $pageView = new PageView(
            settings: $settings,
            page: $page,
        );

        self::assertSame($settings, $pageView->settings);
        self::assertSame($page, $pageView->page);
        self::assertSame([], $pageView->notifications);
        self::assertNull($pageView->session);
        self::assertSame(0, $pageView->sessionTimeout);
        self::assertSame('', $pageView->sessionTimeoutTime);
        self::assertSame(0, $pageView->deletedItems);
        self::assertSame('', $pageView->timeoutTemplate);
        self::assertFalse($pageView->twoFactorPending);
    }

    #[Test]
    public function itCreatesAPageViewWithAllValues(): void
    {
        $settings = new \ReflectionClass(SiteSettings::class)
            ->newInstanceWithoutConstructor();

        $page = new \ReflectionClass(PageMetadata::class)
            ->newInstanceWithoutConstructor();

        $notifications = [
            'First notification',
            'Second notification',
        ];

        $session = new \stdClass();

        $pageView = new PageView(
            settings: $settings,
            page: $page,
            notifications: $notifications,
            session: $session,
            sessionTimeout: 300,
            sessionTimeoutTime: '12:30',
            deletedItems: 5,
            timeoutTemplate: 'security/session_timeout.html.twig',
            twoFactorPending: true,
        );

        self::assertSame($settings, $pageView->settings);
        self::assertSame($page, $pageView->page);
        self::assertSame($notifications, $pageView->notifications);
        self::assertSame($session, $pageView->session);
        self::assertSame(300, $pageView->sessionTimeout);
        self::assertSame('12:30', $pageView->sessionTimeoutTime);
        self::assertSame(5, $pageView->deletedItems);
        self::assertSame(
            'security/session_timeout.html.twig',
            $pageView->timeoutTemplate,
        );
        self::assertTrue($pageView->twoFactorPending);
    }
}
