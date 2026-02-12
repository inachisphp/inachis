<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\Page;

use Inachis\Entity\Category;
use Inachis\Entity\Series;
use Inachis\Entity\User;
use Inachis\Model\BulkCreateData;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\TagRepository;
use Inachis\Service\Page\PageBulkCreateService;
use DateTimeImmutable;
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
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->seriesRepository = $this->createStub(SeriesRepository::class);
        $this->tagRepository = $this->createStub(TagRepository::class);
        $this->categoryRepository = $this->createStub(CategoryRepository::class);
        $this->bulkCreateData = new BulkCreateData(
            'some title',
            DateTimeImmutable::createFromFormat('d/m/Y', '01/11/2025'),
            DateTimeImmutable::createFromFormat('d/m/Y', '07/11/2025'),
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