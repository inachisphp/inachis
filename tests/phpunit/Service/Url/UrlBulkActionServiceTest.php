<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\Url;

use App\Entity\Page;
use App\Entity\Url;
use App\Repository\UrlRepository;
use App\Service\Url\UrlBulkActionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UrlBulkActionServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private Url $url;
    private UrlRepository $urlRepository;

    private UrlBulkActionService $urlBulkActionService;

    /**
     * @throws Exception
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $page = new Page();
        $this->url = (new Url($page))
            ->setId(Uuid::uuid4());
        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->urlRepository->method('findOneBy')->willReturn($this->url);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->urlBulkActionService = new UrlBulkActionService(
            $this->urlRepository,
            $this->entityManager
        );
    }

    /**
     * @throws Exception
     */
    public function testApplyUrlNotFound(): void
    {
        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->urlRepository->method('findOneBy')->willReturn(null);
        $this->urlBulkActionService = new UrlBulkActionService(
            $this->urlRepository,
            $this->entityManager
        );
        $result = $this->urlBulkActionService->apply('', [Uuid::uuid1()->toString()]);
        $this->assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDelete(): void
    {
        $result = $this->urlBulkActionService->apply('delete', [$this->url->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyMakeDefault(): void
    {
        $result = $this->urlBulkActionService->apply('make_default', [$this->url->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDefault(): void
    {
        $result = $this->urlBulkActionService->apply('', [$this->url->getId()]);
        $this->assertEquals(1, $result);
    }
}
