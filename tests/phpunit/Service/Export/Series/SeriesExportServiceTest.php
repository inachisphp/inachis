<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Export\Series;

use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Export\Page\PageJsonWriter;
use Inachis\Service\Export\Series\SeriesExportNormaliser;
use Inachis\Service\Export\Series\SeriesExportService;
use PHPUnit\Framework\TestCase;

final class SeriesExportServiceTest extends TestCase
{
    public function testExportSeriesStubs(): void
    {
        $repo = $this->createMock(SeriesRepository::class);
        $normaliser = new SeriesExportNormaliser();
        $jsonWriter = new PageJsonWriter();

        $service = new SeriesExportService($repo, $normaliser, [$jsonWriter]);

        $series = new Series();
        $series->setTitle('My Series');
        $series->setUrl('my-series');
        $page = new Page('Page 1');
        $series->addItem($page);

        $json = $service->export([$series], 'json', false);

        $this->assertStringContainsString('My Series', $json);
        $this->assertStringContainsString('Page 1', $json);
    }
}
