<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\Service\Content;

use App\Entity\Page;
use App\Entity\Series;
use App\Repository\PageRepository;
use App\Repository\SeriesRepository;
use App\Service\Content\ContentAggregator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ContentAggregatorTest extends TestCase
{
    public function testGetHomepageContent(): void
    {
        $seriesRepo = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $pageRepo = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($seriesRepo, $pageRepo) {
                return $class === Series::class ? $seriesRepo : $pageRepo;
            });
        $seriesItemPage = $this->createConfiguredMock(Page::class, [
            'getId' => Uuid::uuid1(),
        ]);
        $seriesGroup = $this->createMock(Series::class);
        $seriesGroup->method('getItems')
            ->willReturn(new ArrayCollection([$seriesItemPage]));
        $seriesGroup->method('getLastDate')->willReturn(new DateTime('2024-01-02'));
        $seriesGroup->method('getDescription')->willReturn('Some <blockquote>test</blockquote>');
        $seriesGroup->expects($this->once())->method('setDescription');
        $seriesRepo->method('getAll')
            ->willReturn($this->createMockPaginator([$seriesGroup]));

        $pageResult = $this->createMock(Page::class);
        $pageResult->method('getPostDate')->willReturn(new DateTime('2024-01-01'));
        $pageRepo->method('getAll')
            ->willReturn($this->createMockPaginator([$pageResult]));

        $aggregator = new ContentAggregator($entityManager);

        $result = $aggregator->getHomepageContent();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('20240102', $result);
        $this->assertArrayHasKey('20240101', $result);
    }

    private function createMockPaginator(array $items): Paginator
    {
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator', 'count'])
            ->getMock();

        $paginator->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        $paginator->method('count')
            ->willReturn(count($items));

        return $paginator;
    }
}