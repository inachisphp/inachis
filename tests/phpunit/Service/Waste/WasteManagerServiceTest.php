<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Waste;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Content\Tag;
use Inachis\Entity\Content\Url;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Entity\Waste\Waste;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\PageRepository;
use Inachis\Service\Waste\WasteManagerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;

class WasteManagerServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PageRepository $pageRepository;
    private Security $security;
    private Filesystem&MockObject $filesystem;
    private string $imageDir = '/tmp/test_images/';
    private WasteManagerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pageRepository = $this->createStub(PageRepository::class);
        $this->security = $this->createStub(Security::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->service = new WasteManagerService(
            $this->entityManager,
            $this->pageRepository,
            $this->security,
            $this->filesystem,
            $this->imageDir,
        );
    }

    public function testSendToWasteThrowsExceptionForUnsupportedEntity(): void
    {
        $user = $this->createStubObject(User::class);
        $this->security->method('getUser')->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported entity type for waste');

        $unsupportedEntity = new class {
            public function getId(): string
            {
                return '123';
            }
        };

        $this->service->sendToWaste($unsupportedEntity);
    }

    public function testSendToWasteWithPage(): void
    {
        $user = $this->createStubObject(User::class);
        $this->security->method('getUser')->willReturn($user);

        $uuid = Uuid::uuid4();
        $author = $this->createStubObject(User::class);
        if (method_exists($author, 'getId')) {
            $author->method('getId')->willReturn(Uuid::uuid4());
        }

        $featureImage = $this->createStubObject(Image::class);
        if (method_exists($featureImage, 'getId')) {
            $featureImage->method('getId')->willReturn(Uuid::uuid4());
        }

        $catUuid = Uuid::uuid4();
        $category = $this->createStubObject(Category::class);
        if (method_exists($category, 'getId')) {
            $category->method('getId')->willReturn($catUuid);
        }

        $tagUuid = Uuid::uuid4();
        $tag = $this->createStubObject(Tag::class);
        if (method_exists($tag, 'getId')) {
            $tag->method('getId')->willReturn($tagUuid);
        }

        $url = $this->createStubObject(Url::class);
        if (method_exists($url, 'getLink')) {
            $url->method('getLink')->willReturn('/test-page');
            $url->method('isDefault')->willReturn(true);
        }

        $page = $this->createStubObject(Page::class);
        $page->method('getId')->willReturn($uuid);
        $page->method('getTitle')->willReturn('Test Page Title');
        $page->method('getSubTitle')->willReturn('Test Subtitle');
        $page->method('getContent')->willReturn('Page content');
        $page->method('getAuthor')->willReturn($author);
        $page->method('getStatus')->willReturn(EditorialStatus::PUBLISHED);
        $page->method('isVisible')->willReturn(true);
        $page->method('getPostDate')->willReturn(new \DateTimeImmutable('2026-08-10 10:00:00'));
        $page->method('getTimezone')->willReturn('UTC');
        $page->method('getPassword')->willReturn('secret');
        $page->method('isAllowComments')->willReturn(true);
        $page->method('getType')->willReturn('post');
        $page->method('getFeatureSnippet')->willReturn('Snippet');
        $page->method('getFeatureImage')->willReturn($featureImage);
        $page->method('getCategories')->willReturn(new ArrayCollection([$category]));
        $page->method('getTags')->willReturn(new ArrayCollection([$tag]));
        $page->method('getUrls')->willReturn(new ArrayCollection([$url]));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Waste $waste): bool {
                $this->assertSame('Page', $waste->getSourceType());
                $this->assertSame('Test Page Title', $waste->getSourceName());
                $this->assertSame('Test Page Title', $waste->getTitle());

                $data = json_decode($waste->getContent() ?? '', true);
                $this->assertIsArray($data);
                $this->assertSame('Test Page Title', $data['title']);

                return true;
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->sendToWaste($page);
    }

    public function testSendToWasteWithSeries(): void
    {
        $user = $this->createStubObject(User::class);
        $this->security->method('getUser')->willReturn($user);

        $uuid = Uuid::uuid4();
        $series = $this->createStubObject(Series::class);
        $series->method('getId')->willReturn($uuid);
        $series->method('getTitle')->willReturn('Test Series');
        $series->method('getSubTitle')->willReturn('Series Subtitle');
        $series->method('getDescription')->willReturn('Description');
        $series->method('getAuthor')->willReturn(null);
        $series->method('isVisible')->willReturn(true);
        $series->method('getUrl')->willReturn('/series/test');
        $series->method('getImage')->willReturn(null);
        $series->method('getItems')->willReturn(new ArrayCollection([]));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Waste $waste): bool {
                $this->assertSame('Series', $waste->getSourceType());
                $this->assertSame('Test Series', $waste->getSourceName());

                return true;
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->sendToWaste($series);
    }

    public function testSendToWasteWithImageAndFilesystemMove(): void
    {
        $user = $this->createStubObject(User::class);
        $this->security->method('getUser')->willReturn($user);

        $uuid = Uuid::uuid4();
        $image = $this->createStubObject(Image::class);
        $image->method('getId')->willReturn($uuid);
        $image->method('getFilename')->willReturn('photo.jpg');
        $image->method('getTitle')->willReturn('Photo');
        $image->method('getDescription')->willReturn('Sample photo');
        $image->method('getAltText')->willReturn('Alt text');
        $image->method('getFiletype')->willReturn('image/jpeg');
        $image->method('getFilesize')->willReturn(1024);
        $image->method('getChecksum')->willReturn('checksum123');
        $image->method('getDimensionX')->willReturn(800);
        $image->method('getDimensionY')->willReturn(600);
        $image->method('getAuthor')->willReturn(null);

        $sourcePath = $this->imageDir . 'photo.jpg';
        $wasteDir = $this->imageDir . '.waste/';
        $wastePath = $wasteDir . 'photo.jpg';

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturnMap([
                [$sourcePath, true],
                [$wasteDir, false],
            ]);

        $this->filesystem->expects($this->once())->method('mkdir')->with($wasteDir);
        $this->filesystem->expects($this->once())->method('rename')->with($sourcePath, $wastePath);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->sendToWaste($image);
    }

    public function testRestoreThrowsExceptionWhenContentIsInvalidJson(): void
    {
        $waste = new Waste();
        $waste->setContent('INVALID_JSON{');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode waste content');

        $this->service->restore($waste);
    }

    public function testRestoreThrowsExceptionForUnknownSourceType(): void
    {
        $waste = new Waste();
        $waste->setSourceType('UnknownType');
        $waste->setContent(json_encode(['id' => (string) Uuid::uuid4()]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown source type in waste for restore');

        $this->service->restore($waste);
    }

    public function testRestorePageRestoresNewPageAndAssociations(): void
    {
        $pageId = (string) Uuid::uuid4();
        $authorId = (string) Uuid::uuid4();
        $imageId = (string) Uuid::uuid4();
        $catId = (string) Uuid::uuid4();
        $tagId = (string) Uuid::uuid4();

        $waste = new Waste();
        $waste->setSourceType('Page');
        $waste->setContent(json_encode([
            'id' => $pageId,
            'title' => 'Restored Page',
            'subTitle' => 'Subtitle',
            'content' => 'Restored content',
            'status' => EditorialStatus::PUBLISHED->value,
            'visible' => true,
            'postDate' => '2026-08-10 12:00:00',
            'timezone' => 'UTC',
            'password' => null,
            'allowComments' => true,
            'type' => 'post',
            'featureSnippet' => 'Snippet',
            'author' => $authorId,
            'featureImage' => $imageId,
            'categories' => [$catId],
            'tags' => [$tagId],
            'urls' => [
                ['link' => '/restored-page', 'default' => true],
            ],
        ]));

        $this->pageRepository->method('findOneBy')->willReturnMap([
            [['id' => $pageId], null],
        ]);

        $userRepo = $this->createStub(EntityRepository::class);
        $userRepo->method('findOneBy')->willReturnMap([
            [['id' => $authorId], $this->createStubObject(User::class)],
        ]);

        $imageRepo = $this->createStub(EntityRepository::class);
        $imageRepo->method('findOneBy')->willReturnMap([
            [['id' => $imageId], $this->createStubObject(Image::class)],
        ]);

        $catRepo = $this->createStub(EntityRepository::class);
        $catRepo->method('findOneBy')->willReturnMap([
            [['id' => $catId], $this->createStubObject(Category::class)],
        ]);

        $tagRepo = $this->createStub(EntityRepository::class);
        $tagRepo->method('findOneBy')->willReturnMap([
            [['id' => $tagId], $this->createStubObject(Tag::class)],
        ]);

        $urlRepo = $this->createStub(EntityRepository::class);
        $urlRepo->method('findOneBy')->willReturnMap([
            [['link' => '/restored-page'], null],
        ]);

        $this->entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepo],
            [Image::class, $imageRepo],
            [Category::class, $catRepo],
            [Tag::class, $tagRepo],
            [Url::class, $urlRepo],
        ]);

        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('remove')->with($waste);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->restore($waste);
    }

    public function testRestoreSeriesRestoresSeriesAndItems(): void
    {
        $seriesId = (string) Uuid::uuid4();
        $itemId = (string) Uuid::uuid4();

        $waste = new Waste();
        $waste->setSourceType('Series');
        $waste->setContent(json_encode([
            'id' => $seriesId,
            'title' => 'Restored Series',
            'subTitle' => 'Subtitle',
            'description' => 'Description',
            'visible' => true,
            'url' => '/series/restored',
            'items' => [$itemId],
        ]));

        $seriesRepo = $this->createStub(EntityRepository::class);
        $seriesRepo->method('findOneBy')->willReturnMap([
            [['id' => $seriesId], null],
        ]);

        $pageRepo = $this->createStub(EntityRepository::class);
        $pageRepo->method('findOneBy')->willReturnMap([
            [['id' => $itemId], $this->createStubObject(Page::class)],
        ]);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Series::class, $seriesRepo],
            [Page::class, $pageRepo],
        ]);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('remove')->with($waste);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->restore($waste);
    }

    public function testRestoreImageMovesFileBackFromWaste(): void
    {
        $imageId = (string) Uuid::uuid4();

        $waste = new Waste();
        $waste->setSourceType('Image');
        $waste->setContent(json_encode([
            'id' => $imageId,
            'title' => 'Restored Image',
            'filename' => 'restored.jpg',
            'filetype' => 'image/jpeg',
            'filesize' => 2048,
            'checksum' => 'abc',
            'dimensionX' => 1024,
            'dimensionY' => 768,
        ]));

        $imageRepo = $this->createStub(EntityRepository::class);
        $imageRepo->method('findOneBy')->willReturnMap([
            [['id' => $imageId], null],
        ]);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Image::class, $imageRepo],
        ]);

        $wastePath = $this->imageDir . '.waste/restored.jpg';
        $targetPath = $this->imageDir . 'restored.jpg';

        $this->filesystem->expects($this->once())->method('exists')->with($wastePath)->willReturn(true);
        $this->filesystem->expects($this->once())->method('rename')->with($wastePath, $targetPath);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('remove')->with($waste);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->restore($waste);
    }

    public function testDeleteWasteRemovesWasteForNonImage(): void
    {
        $waste = new Waste();
        $waste->setSourceType('Page');

        $this->entityManager->expects($this->once())->method('remove')->with($waste);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->deleteWaste($waste);
    }

    public function testDeleteWasteRemovesFileAndWasteForImage(): void
    {
        $waste = new Waste();
        $waste->setSourceType('Image');
        $waste->setContent(json_encode(['filename' => 'to_delete.jpg']));

        $wastePath = $this->imageDir . '.waste/to_delete.jpg';

        $this->filesystem->expects($this->once())->method('exists')->with($wastePath)->willReturn(true);
        $this->filesystem->expects($this->once())->method('remove')->with($wastePath);

        $this->entityManager->expects($this->once())->method('remove')->with($waste);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->deleteWaste($waste);
    }

    /**
     * Helper to safely instantiate or stub objects whether they are final or normal classes.
     */
    private function createStubObject(string $className): object
    {
        $reflection = new \ReflectionClass($className);

        if ($reflection->isFinal()) {
            return $reflection->newInstanceWithoutConstructor();
        }

        return $this->createStub($className);
    }
}
