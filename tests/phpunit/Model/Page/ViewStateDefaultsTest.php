<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Model\Page;

use Inachis\Model\Page\ViewStateDefaults;
use PHPUnit\Framework\TestCase;

final class ViewStateDefaultsTest extends TestCase
{
    public function testConstructorAssignsValues(): void
    {
        $filters = [
            'status' => 'published',
            'category' => 'travel',
        ];

        $defaults = new ViewStateDefaults(
            filters: $filters,
            sort: 'title asc',
            view: 'table',
        );

        $this->assertSame($filters, $defaults->filters);
        $this->assertSame('title asc', $defaults->sort);
        $this->assertSame('table', $defaults->view);

        $this->assertSame($filters, $defaults->getFilters());
        $this->assertSame('title asc', $defaults->getSort());
        $this->assertSame('table', $defaults->getView());
    }

    public function testDefaultsAreUsedWhenNoArgumentsProvided(): void
    {
        $defaults = new ViewStateDefaults();

        $this->assertSame([], $defaults->filters);
        $this->assertSame('', $defaults->sort);
        $this->assertSame('list', $defaults->view);

        $this->assertSame([], $defaults->getFilters());
        $this->assertSame('', $defaults->getSort());
        $this->assertSame('list', $defaults->getView());
    }
}
