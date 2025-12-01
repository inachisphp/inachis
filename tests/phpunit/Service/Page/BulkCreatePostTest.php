<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\Page;

use App\Entity\Category;
use App\Entity\Series;
use App\Entity\User;
use App\Model\BulkCreateData;
use App\Repository\CategoryRepository;
use App\Repository\SeriesRepository;
use App\Repository\TagRepository;
use App\Service\Page\PageBulkCreateService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class BulkCreatePostTest extends TestCase
{
    protected BulkCreateData $bulkCreateData;
    protected EntityManagerInterface $entityManager;
    protected SeriesRepository $seriesRepository;
    protected TagRepository $tagRepository;
    protected CategoryRepository $categoryRepository;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->seriesRepository = $this->createMock(SeriesRepository::class);
        $this->tagRepository = $this->createMock(TagRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->bulkCreateData = new BulkCreateData(
            'some title',
            DateTime::createFromFormat('d/m/Y', '01/11/2025'),
            DateTime::createFromFormat('d/m/Y', '07/11/2025'),
            false,
            Uuid::uuid1()->toString(),
            [ Uuid::uuid1() ],
            [ Uuid::uuid1() ],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateInvalidSeries(): void
    {
        $bulkCreatePost = new PageBulkCreateService(
            $this->entityManager,
            $this->seriesRepository,
            $this->tagRepository,
            $this->categoryRepository,
        );
        $this->expectExceptionMessage('Series not found');
        $bulkCreatePost->create($this->bulkCreateData, new User());
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $series = (new Series())->setId(Uuid::fromString($this->bulkCreateData->seriesId));
        $this->seriesRepository->method('find')->willReturn($series);
        $this->categoryRepository->method('findOneBy')->willReturn(new Category());
        $bulkCreatePost = new PageBulkCreateService(
            $this->entityManager,
            $this->seriesRepository,
            $this->tagRepository,
            $this->categoryRepository,
        );
        $result = $bulkCreatePost->create($this->bulkCreateData, new User());
        $this->assertEquals(7, $result);
    }
}