<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Import\Page\PageImportMapper;
use PHPUnit\Framework\TestCase;

final class PageImportMapperTest extends TestCase
{
    public function testMapItemToDto(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $mapper = new PageImportMapper($em);

        $data = [
            'title' => 'Test Page',
            'subTitle' => 'Test Subtitle',
            'content' => 'Page content',
            'type' => 'post',
            'status' => 'published',
            'categories' => ['Trips/Europe/France'],
            'tags' => ['travel', 'vacation'],
            'urls' => [
                ['path' => '2026/08/test-page', 'default' => true],
            ],
        ];

        $dto = $mapper->mapItemToDto($data);

        $this->assertInstanceOf(PageExportDto::class, $dto);
        $this->assertSame('Test Page', $dto->title);
        $this->assertSame('Test Subtitle', $dto->subTitle);
        $this->assertCount(1, $dto->categories);
        $this->assertSame('Trips/Europe/France', $dto->categories[0]->path);
        $this->assertCount(2, $dto->tags);
        $this->assertSame('travel', $dto->tags[0]->title);
        $this->assertCount(1, $dto->urls);
        $this->assertSame('2026/08/test-page', $dto->urls[0]->path);
    }
}
