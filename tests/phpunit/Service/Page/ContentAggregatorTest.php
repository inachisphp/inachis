<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\Page;

use App\Entity\Page;
use App\Entity\Series;
use App\Repository\PageRepository;
use App\Repository\SeriesRepository;
use App\Service\Page\ContentAggregator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ContentAggregatorTest extends TestCase
{
    public function testGetHomepageContent(): void
    {
        $seriesRepository = $this->getStubBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getStub();
        $pageRepository = $this->getStubBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getStub();
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($seriesRepository, $pageRepository) {
                return $class === Series::class ? $seriesRepository : $pageRepository;
            });
        $seriesItemPage = $this->createConfiguredStub(Page::class, [
            'getId' => Uuid::uuid1(),
        ]);
        $seriesGroup = $this->createStub(Series::class);
        $seriesGroup->method('getItems')
            ->willReturn(new ArrayCollection([$seriesItemPage]));
        $seriesGroup->method('getLastDate')->willReturn(new DateTime('2024-01-02'));
        $seriesGroup->method('getDescription')->willReturn('Some <blockquote>test</blockquote>');
        $seriesGroup->method('setDescription');
        $seriesRepository->method('getAll')
            ->willReturn($this->createMockPaginator([$seriesGroup]));

        $pageResult = $this->createStub(Page::class);
        $pageResult->method('getPostDate')->willReturn(new DateTime('2024-01-01'));
        $pageRepository->method('getAll')
            ->willReturn($this->createMockPaginator([$pageResult]));

        $aggregator = new ContentAggregator($pageRepository, $seriesRepository);

        $result = $aggregator->getHomepageContent();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('20240102', $result);
        $this->assertArrayHasKey('20240101', $result);
    }

    private function createMockPaginator(array $items): Paginator
    {
        $paginator = $this->getStubBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator', 'count'])
            ->getStub();

        $paginator->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        $paginator->method('count')
            ->willReturn(count($items));

        return $paginator;
    }
}