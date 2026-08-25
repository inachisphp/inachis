<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Tag;
use Inachis\Entity\User\User;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\CategoryPathDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Page\TagDto;
use Inachis\Model\Page\UrlDto;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Service\Import\Page\CategoryImportService;
use Inachis\Service\Import\Page\PageImportService;
use Inachis\Service\Import\Page\TagImportService;
use PHPUnit\Framework\TestCase;

final class PageImportServiceTest extends TestCase
{
    public function testImportPageWithCategoryTagUrl(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $categoryRepo = $this->createMock(EntityRepository::class);
        $tagRepo = $this->createMock(EntityRepository::class);
        $urlRepo = $this->createMock(UrlRepository::class);

        $em->method('getRepository')
            ->willReturnCallback(function (string $class) use ($categoryRepo, $tagRepo) {
                if (Category::class === $class) {
                    return $categoryRepo;
                }
                if (Tag::class === $class) {
                    return $tagRepo;
                }

                return null;
            });

        $categoryRepo->method('findOneBy')->willReturn(null);
        $tagRepo->method('findOneBy')->willReturn(null);

        $categoryService = new CategoryImportService($em);
        $tagService = new TagImportService($em);

        $urlRepo->expects($this->once())
            ->method('getUniqueUrl')
            ->with('2026/08/france-trip')
            ->willReturn('2026/08/france-trip-1');

        $service = new PageImportService($em, $categoryService, $tagService, $urlRepo);

        $dto = new PageExportDto();
        $dto->title = 'France Trip';
        $dto->content = 'Awesome trip';
        $dto->type = 'post';
        $dto->status = 'draft';

        $catDto = new CategoryPathDto();
        $catDto->path = 'Trips/France';
        $dto->categories[] = $catDto;

        $tagDto = new TagDto();
        $tagDto->title = 'Travel';
        $dto->tags[] = $tagDto;

        $urlDto = new UrlDto();
        $urlDto->path = '2026/08/france-trip';
        $dto->urls[] = $urlDto;

        $author = new User('author', 'author@example.com');
        $result = $service->import([$dto], $author, new ImportOptionsDto());

        $this->assertSame(1, $result->pagesImported);
        $this->assertSame(1, $result->categoriesCreated);
        $this->assertSame(1, $result->tagsCreated);
    }

    public function testImportBatchWithDuplicateTagsAcrossPages(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $categoryRepo = $this->createMock(EntityRepository::class);
        $tagRepo = $this->createMock(EntityRepository::class);
        $urlRepo = $this->createMock(UrlRepository::class);

        $em->method('getRepository')
            ->willReturnCallback(function (string $class) use ($categoryRepo, $tagRepo) {
                if (Category::class === $class) {
                    return $categoryRepo;
                }
                if (Tag::class === $class) {
                    return $tagRepo;
                }

                return null;
            });

        $categoryRepo->method('findOneBy')->willReturn(null);
        $tagRepo->method('findOneBy')->willReturn(null);

        $categoryService = new CategoryImportService($em);
        $tagService = new TagImportService($em);

        $service = new PageImportService($em, $categoryService, $tagService, $urlRepo);

        $dto1 = new PageExportDto();
        $dto1->title = 'Page 1';
        $dto1->status = 'draft';
        $tagDto1 = new TagDto();
        $tagDto1->title = 'Travel';
        $dto1->tags[] = $tagDto1;

        $dto2 = new PageExportDto();
        $dto2->title = 'Page 2';
        $dto2->status = 'draft';
        $tagDto2 = new TagDto();
        $tagDto2->title = 'travel';
        $dto2->tags[] = $tagDto2;

        $author = new User('author', 'author@example.com');
        $result = $service->import([$dto1, $dto2], $author, new ImportOptionsDto());

        $this->assertSame(2, $result->pagesImported);
        $this->assertSame(1, $result->tagsCreated);
    }
}
