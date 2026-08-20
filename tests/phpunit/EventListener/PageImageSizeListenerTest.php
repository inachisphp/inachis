<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Media\Image;
use Inachis\EventListener\PageImageSizeListener;
use Inachis\Repository\Media\ImageRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PageImageSizeListenerTest extends TestCase
{
    private ImageRepository&MockObject $imageRepository;
    private PageImageSizeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageRepository = $this->createMock(ImageRepository::class);
        $this->listener = new PageImageSizeListener($this->imageRepository);
    }

    #[Test]
    public function itReturnsSubscribedEvents(): void
    {
        self::assertSame(
            [
                Events::prePersist,
                Events::preUpdate,
            ],
            $this->listener->getSubscribedEvents(),
        );
    }

    #[Test]
    public function itDoesNothingWhenEntityIsNotAPageOnPrePersist(): void
    {
        $nonPageEntity = new \stdClass();

        $this->imageRepository
            ->expects(self::never())
            ->method('findBy');

        $event = new PrePersistEventArgs($nonPageEntity, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($event);
    }

    #[Test]
    public function itCalculatesZeroImageSizeWhenNoImagesOrFeatureImagePresent(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getContent')->willReturn('This is plain text with no images.');
        $page->method('getFeatureImage')->willReturn(null);

        $this->imageRepository
            ->expects(self::never())
            ->method('findBy');

        $page->expects(self::once())
            ->method('setImageSize')
            ->with(0);

        $event = new PrePersistEventArgs($page, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($event);
    }

    #[Test]
    public function itCalculatesImageSizeFromMarkdownContentOnPrePersist(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getContent')->willReturn(
            '![Hero](/imgs/banner.jpg) Some text ![Icon](/imgs/icon.png) Duplicate ![Hero](/imgs/banner.jpg)',
        );
        $page->method('getFeatureImage')->willReturn(null);

        $image1 = $this->createMock(Image::class);
        $image1->method('getFilesize')->willReturn(2048);

        $image2 = $this->createMock(Image::class);
        $image2->method('getFilesize')->willReturn(1024);

        $this->imageRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['filename' => ['banner.jpg', 'icon.png']])
            ->willReturn([$image1, $image2]);

        $page->expects(self::once())
            ->method('setImageSize')
            ->with(3072);

        $event = new PrePersistEventArgs($page, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($event);
    }

    #[Test]
    public function itCalculatesImageSizeFromContentAndFeatureImageOnPreUpdate(): void
    {
        $featureImage = $this->createMock(Image::class);
        $featureImage->method('getFilesize')->willReturn(5000);

        $contentImage = $this->createMock(Image::class);
        $contentImage->method('getFilesize')->willReturn(1500);

        $page = $this->createMock(Page::class);
        $page->method('getContent')->willReturn('![Inline](/imgs/photo.jpg)');
        $page->method('getFeatureImage')->willReturn($featureImage);

        $this->imageRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['filename' => ['photo.jpg']])
            ->willReturn([$contentImage]);

        $page->expects(self::once())
            ->method('setImageSize')
            ->with(6500);

        $changeSet = [];
        $event = new PreUpdateEventArgs($page, $this->createMock(EntityManagerInterface::class), $changeSet);

        $this->listener->preUpdate($event);
    }
}
