<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Model\NavigationTabDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NavigationTabDtoTest extends TestCase
{
    #[Test]
    public function itCreatesANavigationTab(): void
    {
        $tab = new NavigationTabDto(
            'dashboard',
            'Dashboard',
            '/admin/dashboard',
            1,
        );

        self::assertSame('dashboard', $tab->id);
        self::assertSame('Dashboard', $tab->title);
        self::assertSame('/admin/dashboard', $tab->url);
        self::assertSame(1, $tab->position);
    }

    #[Test]
    public function itCreatesANavigationTabFromAnArray(): void
    {
        $tab = NavigationTabDto::fromArray([
            'id' => 'dashboard',
            'title' => 'Dashboard',
            'url' => '/admin/dashboard',
            'position' => 1,
        ]);

        self::assertSame('dashboard', $tab->id);
        self::assertSame('Dashboard', $tab->title);
        self::assertSame('/admin/dashboard', $tab->url);
        self::assertSame(1, $tab->position);
    }

    /**
     * @return iterable<string, array{
     *     row: array<string, string|int>,
     *     id: string,
     *     title: string,
     *     url: string,
     *     position: int
     * }>
     */
    public static function fromArrayProvider(): iterable
    {
        yield 'integer values' => [
            'row' => [
                'id' => 123,
                'title' => 456,
                'url' => 789,
                'position' => 10,
            ],
            'id' => '123',
            'title' => '456',
            'url' => '789',
            'position' => 10,
        ];

        yield 'string values' => [
            'row' => [
                'id' => 'settings',
                'title' => 'Settings',
                'url' => '/admin/settings',
                'position' => 2,
            ],
            'id' => 'settings',
            'title' => 'Settings',
            'url' => '/admin/settings',
            'position' => 2,
        ];
    }

    #[Test]
    #[DataProvider('fromArrayProvider')]
    public function itCreatesANavigationTabFromDifferentArrayValueTypes(
        array $row,
        string $id,
        string $title,
        string $url,
        int $position,
    ): void {
        $tab = NavigationTabDto::fromArray($row);

        self::assertSame($id, $tab->id);
        self::assertSame($title, $tab->title);
        self::assertSame($url, $tab->url);
        self::assertSame($position, $tab->position);
    }
}
