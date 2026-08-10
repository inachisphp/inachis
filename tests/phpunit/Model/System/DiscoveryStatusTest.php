<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Model\System\DiscoveryStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DiscoveryStatusTest extends TestCase
{
    #[Test]
    public function itCreatesAStatusWithDefaultValues(): void
    {
        $status = new DiscoveryStatus(
            title: 'Test resource',
            description: 'A test discovery resource.',
            status: DiscoveryStatus::SUCCESS,
        );

        self::assertSame('Test resource', $status->title);
        self::assertSame('A test discovery resource.', $status->description);
        self::assertSame(DiscoveryStatus::SUCCESS, $status->status);
        self::assertNull($status->url);
        self::assertSame([], $status->messages);
        self::assertSame('documents', $status->group);
    }

    #[Test]
    public function itCreatesAStatusWithAllValues(): void
    {
        $messages = [
            'First message',
            'Second message',
        ];

        $status = new DiscoveryStatus(
            title: 'Test resource',
            description: 'A test discovery resource.',
            status: DiscoveryStatus::WARNING,
            url: '/admin/discovery',
            messages: $messages,
            group: 'system',
        );

        self::assertSame('Test resource', $status->title);
        self::assertSame('A test discovery resource.', $status->description);
        self::assertSame(DiscoveryStatus::WARNING, $status->status);
        self::assertSame('/admin/discovery', $status->url);
        self::assertSame($messages, $status->messages);
        self::assertSame('system', $status->group);
    }

    #[Test]
    #[DataProvider('healthyStatusProvider')]
    public function itDeterminesWhetherAStatusIsHealthy(
        string $statusValue,
        bool $expected,
    ): void {
        $status = new DiscoveryStatus(
            title: 'Test resource',
            description: 'A test discovery resource.',
            status: $statusValue,
        );

        self::assertSame($expected, $status->isHealthy());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function healthyStatusProvider(): iterable
    {
        yield 'success is healthy' => [
            DiscoveryStatus::SUCCESS,
            true,
        ];

        yield 'warning is not healthy' => [
            DiscoveryStatus::WARNING,
            false,
        ];

        yield 'error is not healthy' => [
            DiscoveryStatus::ERROR,
            false,
        ];

        yield 'unknown status is not healthy' => [
            'unknown',
            false,
        ];
    }

    #[Test]
    #[DataProvider('statusIconProvider')]
    public function itReturnsTheCorrectStatusIcon(
        string $statusValue,
        string $expectedIcon,
    ): void {
        $status = new DiscoveryStatus(
            title: 'Test resource',
            description: 'A test discovery resource.',
            status: $statusValue,
        );

        self::assertSame($expectedIcon, $status->getStatusIcon());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function statusIconProvider(): iterable
    {
        yield 'success' => [
            DiscoveryStatus::SUCCESS,
            'check_circle',
        ];

        yield 'warning' => [
            DiscoveryStatus::WARNING,
            'warning',
        ];

        yield 'error' => [
            DiscoveryStatus::ERROR,
            'error',
        ];

        yield 'unknown status' => [
            'unknown',
            'help',
        ];
    }
}
