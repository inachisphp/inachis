<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Service\Page;

use App\Entity\Page;
use App\Entity\Url;
use App\Repository\PageRepository;
use App\Repository\RevisionRepository;
use App\Repository\UrlRepository;
use App\Service\Page\PageBulkActionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class PageBulkActionServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private Page $page;
    private PageRepository $pageRepository;

    private PageBulkActionService $pageBulkActionService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->page = (new Page())
            ->setId(Uuid::uuid4())
            ->setTitle('test')
            ->setSubTitle('sub-title');
        $url = new Url($this->page);
        $this->pageRepository = $this->createMock(PageRepository::class);
        $this->pageRepository->method('findOneBy')->willReturn($this->page);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->pageBulkActionService = new PageBulkActionService(
            $this->pageRepository,
            $this->createMock(RevisionRepository::class),
            $this->createMock(UrlRepository::class),
            $this->entityManager
        );
    }

    /**
     * @throws Exception
     */
    public function testApplyPageNotFound(): void
    {
        $this->page = new Page();
        $this->pageRepository = $this->createMock(PageRepository::class);
        $this->pageRepository->method('findOneBy')->willReturn($this->page);
        $this->pageBulkActionService = new PageBulkActionService(
            $this->pageRepository,
            $this->createMock(RevisionRepository::class),
            $this->createMock(UrlRepository::class),
            $this->entityManager
        );
        $result = $this->pageBulkActionService->apply('', [Uuid::uuid1()->toString()]);
        $this->assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDelete(): void
    {
        $result = $this->pageBulkActionService->apply('delete', [$this->page->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyPrivate(): void
    {
        $result = $this->pageBulkActionService->apply('private', [$this->page->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyPublic(): void
    {
        $result = $this->pageBulkActionService->apply('public', [$this->page->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyRebuild(): void
    {
        $result = $this->pageBulkActionService->apply('rebuild', [$this->page->getId()]);
        $this->assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testApplyDefault(): void
    {
        $result = $this->pageBulkActionService->apply('', [$this->page->getId()]);
        $this->assertEquals(1, $result);
    }
}
