<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\Series;

use Inachis\Entity\Series;
use Inachis\Repository\SeriesRepository;
use Inachis\Service\Series\SeriesBulkActionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SeriesBulkActionServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private Series $series;
    private SeriesRepository $seriesRepository;

    private SeriesBulkActionService $seriesBulkActionService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->series = (new Series())
            ->setId(Uuid::uuid4())
            ->setTitle('test')
            ->setSubTitle('sub-title');
        $this->seriesRepository = $this->createStub(SeriesRepository::class);
        $this->seriesRepository->method('findOneBy')->willReturn($this->series);
        $this->entityManager = $this->createStub(EntityManager::class);

        $this->seriesBulkActionService = new SeriesBulkActionService(
            $this->seriesRepository,
            $this->entityManager
        );
    }

    /**
     * @throws Exception
     */
    public function testApplyPageNotFound(): void
    {
        $this->series = new Series();
        $this->seriesRepository = $this->createStub(SeriesRepository::class);
        $this->seriesRepository->method('findOneBy')->willReturn($this->series);
        $this->seriesBulkActionService = new SeriesBulkActionService(
            $this->seriesRepository,
            $this->entityManager
        );
        $result = $this->seriesBulkActionService->apply('', [Uuid::uuid1()->toString()]);
        $this->assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDelete(): void
    {
        $result = $this->seriesBulkActionService->apply('delete', [$this->series->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyPrivate(): void
    {
        $result = $this->seriesBulkActionService->apply('private', [$this->series->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyPublic(): void
    {
        $result = $this->seriesBulkActionService->apply('public', [$this->series->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDefault(): void
    {
        $result = $this->seriesBulkActionService->apply('', [$this->series->getId()]);
        $this->assertEquals(1, $result);
    }
}
