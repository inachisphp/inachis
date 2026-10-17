<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Import\Series;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Repository\Content\PageRepository;
use Inachis\Service\Import\Series\SeriesImportService;
use PHPUnit\Framework\TestCase;

final class SeriesImportServiceTest extends TestCase
{
    public function testMapToDtoWithStubsAndFullPages(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $pageRepo = $this->createMock(PageRepository::class);
        $service = new SeriesImportService($em, $pageRepo);

        $data = [
            [
                'title' => 'My Travel Series',
                'url' => 'my-travel-series',
                'items' => [
                    'Page Stub Title',
                    [
                        'title' => 'Full Page Title',
                        'content' => 'Full content',
                        'urls' => [['path' => 'full-page-url']],
                    ],
                ],
            ],
        ];

        $dtos = $service->mapToDto($data);

        $this->assertCount(1, $dtos);
        $this->assertSame('My Travel Series', $dtos[0]->title);
        $this->assertCount(2, $dtos[0]->items);
        $this->assertSame('Page Stub Title', $dtos[0]->items[0]);
        $this->assertInstanceOf(PageExportDto::class, $dtos[0]->items[1]);
        $this->assertSame('Full Page Title', $dtos[0]->items[1]->title);
    }

    public function testImportSeriesLinksExistingPage(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $pageRepo = $this->createMock(PageRepository::class);
        $page = new Page('Existing Page');

        $pageRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'Existing Page'])
            ->willReturn($page);

        $service = new SeriesImportService($em, $pageRepo);

        $seriesDto = new SeriesExportDto();
        $seriesDto->title = 'Test Series';
        $seriesDto->url = 'test-series';
        $seriesDto->items = ['Existing Page'];

        $result = $service->import([$seriesDto]);

        $this->assertSame(1, $result->seriesImported);
        $this->assertSame(1, $result->pagesLinked);
    }
}
