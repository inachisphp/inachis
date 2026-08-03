<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Entity\Content\Category;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Content\CategoryRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class ContentQueryParametersTest extends TestCase
{
    public function testUsesCurrentValuesWhenRequestIsEmpty(): void
    {
        $current = new ContentQueryParameters(
            filters: ['keyword' => 'existing'],
            sort: 'title asc',
            limit: 20,
            offset: 50,
            view: 'grid',
        );

        $request = new Request();

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->never())
            ->method('findBy');

        $result = ContentQueryParameters::fromRequest(
            $request,
            $current,
            $repository,
        );

        $this->assertSame(['keyword' => 'existing'], $result->getFilters());
        $this->assertSame('title asc', $result->getSort());
        $this->assertSame(20, $result->getLimit());
        $this->assertSame(50, $result->getOffset());
        $this->assertSame('grid', $result->getView());
    }

    public function testReadsRequestValues(): void
    {
        $current = new ContentQueryParameters();

        $request = new Request(
            [],
            [
                'filter' => [
                    'keyword' => 'symfony',
                ],
                'sort' => 'created desc',
                'view' => 'grid',
            ],
            [
                'limit' => 25,
                'offset' => 75,
            ]
        );

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->never())
            ->method('findBy');

        $result = ContentQueryParameters::fromRequest(
            $request,
            $current,
            $repository,
        );

        $this->assertSame(
            ['keyword' => 'symfony'],
            $result->getFilters(),
        );
        $this->assertSame('created desc', $result->getSort());
        $this->assertSame(25, $result->getLimit());
        $this->assertSame(75, $result->getOffset());
        $this->assertSame('grid', $result->getView());
    }

    public function testEmptyCategorySelectionBecomesEmptyArray(): void
    {
        $request = new Request(
            [],
            [
                'filter' => [
                    'categories' => '',
                ],
            ]
        );

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->never())
            ->method('findBy');

        $result = ContentQueryParameters::fromRequest(
            $request,
            new ContentQueryParameters(),
            $repository,
        );

        $this->assertSame([], $result->getFilters());
    }

    public function testCategoryIdsAreConvertedToTitles(): void
    {
        $id = Uuid::uuid4();

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn($id);
        $category->method('getTitle')->willReturn('Travel');

        $request = new Request(
            [],
            [
                'filter' => [
                    'categories' => [$id->toString()],
                ],
            ]
        );

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with([
                'id' => [$id->toString()],
            ])
            ->willReturn([$category]);

        $result = ContentQueryParameters::fromRequest(
            $request,
            new ContentQueryParameters(),
            $repository,
        );

        $this->assertSame(
            [
                'categories' => [
                    $id->toString() => 'Travel',
                ],
            ],
            $result->getFilters(),
        );
    }

    public function testEmptyScalarFiltersAreRemoved(): void
    {
        $request = new Request(
            [],
            [
                'filter' => [
                    'keyword' => '',
                    'author' => '',
                    'status' => 'published',
                ],
            ]
        );

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->never())
            ->method('findBy');

        $result = ContentQueryParameters::fromRequest(
            $request,
            new ContentQueryParameters(),
            $repository,
        );

        $this->assertSame(
            [
                'status' => 'published',
            ],
            $result->getFilters(),
        );
    }

    public function testToArrayReturnsExpectedStructure(): void
    {
        $parameters = new ContentQueryParameters(
            filters: ['keyword' => 'test'],
            sort: 'title asc',
            limit: 50,
            offset: 100,
            view: 'grid',
        );

        $this->assertSame(
            [
                'filters' => ['keyword' => 'test'],
                'sort' => 'title asc',
                'offset' => 100,
                'limit' => 50,
                'view' => 'grid',
            ],
            $parameters->toArray(),
        );
    }
}
