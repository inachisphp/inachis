<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\Page;

use Inachis\Entity\Page;
use Inachis\Entity\Url;
use Inachis\Repository\PageRepository;
use Inachis\Repository\RevisionRepository;
use Inachis\Repository\UrlRepository;
use Inachis\Service\Page\PageBulkActionService;
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
        $this->pageRepository = $this->createStub(PageRepository::class);
        $this->pageRepository->method('findOneBy')->willReturn($this->page);
        $this->entityManager = $this->createStub(EntityManager::class);

        $this->pageBulkActionService = new PageBulkActionService(
            $this->pageRepository,
            $this->createStub(RevisionRepository::class),
            $this->createStub(UrlRepository::class),
            $this->entityManager
        );
    }

    /**
     * @throws Exception
     */
    public function testApplyPageNotFound(): void
    {
        $this->page = new Page();
        $this->pageRepository = $this->createStub(PageRepository::class);
        $this->pageRepository->method('findOneBy')->willReturn($this->page);
        $this->pageBulkActionService = new PageBulkActionService(
            $this->pageRepository,
            $this->createStub(RevisionRepository::class),
            $this->createStub(UrlRepository::class),
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
