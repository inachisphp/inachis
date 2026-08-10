<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Page\RecalculatePageSizesCommand;
use Inachis\Entity\Content\Page;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Media\ImageRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class RecalculatePageSizesCommandTest extends TestCase
{
    #[Test]
    public function itRecalculatesImageSizesForPages(): void
    {
        $page = new Page();
        $page->setContent(
            '<p><img src="/imgs/first.jpg"></p>'
            .'<p><img src="/imgs/second.jpg"></p>',
        );

        $firstImage = $this->createMock(\Inachis\Entity\Media\Image::class);
        $firstImage
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(1000);

        $secondImage = $this->createMock(\Inachis\Entity\Media\Image::class);
        $secondImage
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(2000);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::once())
            ->method('findBy')
            ->with([
                'filename' => [
                    'first.jpg',
                    'second.jpg',
                ],
            ])
            ->willReturn([
                $firstImage,
                $secondImage,
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $output = new BufferedOutput();

        $result = $command->run(
            new ArrayInput([]),
            $output,
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(3000, $page->getImageSize());

        $display = $output->fetch();

        self::assertStringContainsString(
            'Recalculating page image sizes…',
            $display,
        );

        self::assertStringContainsString(
            'Completed recalculating image sizes for 1 pages.',
            $display,
        );
    }

    #[Test]
    public function itIgnoresDuplicateImageReferences(): void
    {
        $page = new Page();
        $page->setContent(
            '<img src="/imgs/example.jpg">'
            .'<img src="/imgs/example.jpg">',
        );

        $image = $this->createMock(\Inachis\Entity\Media\Image::class);
        $image
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(1500);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::once())
            ->method('findBy')
            ->with([
                'filename' => ['example.jpg'],
            ])
            ->willReturn([$image]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(1500, $page->getImageSize());
    }

    #[Test]
    public function itIncludesFeatureImageSize(): void
    {
        $page = new Page();
        $page->setContent('');

        $featureImage = $this->createMock(\Inachis\Entity\Media\Image::class);
        $featureImage
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(5000);

        $page->setFeatureImage($featureImage);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::never())
            ->method('findBy');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(5000, $page->getImageSize());
    }

    #[Test]
    public function itCombinesContentImagesAndFeatureImage(): void
    {
        $page = new Page();
        $page->setContent('<img src="/imgs/content.jpg">');

        $contentImage = $this->createMock(\Inachis\Entity\Media\Image::class);
        $contentImage
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(2500);

        $featureImage = $this->createMock(\Inachis\Entity\Media\Image::class);
        $featureImage
            ->expects(self::once())
            ->method('getFilesize')
            ->willReturn(7500);

        $page->setFeatureImage($featureImage);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::once())
            ->method('findBy')
            ->with([
                'filename' => ['content.jpg'],
            ])
            ->willReturn([$contentImage]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(10000, $page->getImageSize());
    }

    #[Test]
    public function itSetsImageSizeToZeroWhenPageHasNoImages(): void
    {
        $page = new Page();
        $page->setContent('');
        $page->setImageSize(1234);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::never())
            ->method('findBy');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(0, $page->getImageSize());
    }

    #[Test]
    public function itFlushesEveryFiftyPagesAndAtTheEnd(): void
    {
        $pages = [];

        for ($i = 0; $i < 51; ++$i) {
            $page = new Page();
            $page->setContent('');

            $pages[] = $page;
        }

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($pages);

        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository
            ->expects(self::never())
            ->method('findBy');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('flush');

        $command = new RecalculatePageSizesCommand(
            $entityManager,
            $imageRepository,
            $repository,
        );

        $result = $command->run(
            new ArrayInput([]),
            new BufferedOutput(),
        );

        self::assertSame(Command::SUCCESS, $result);
        self::assertCount(51, $pages);

        foreach ($pages as $page) {
            self::assertSame(0, $page->getImageSize());
        }
    }
}
