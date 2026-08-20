<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Media\Image;
use Inachis\EventListener\DashboardCacheListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class DashboardCacheListenerTest extends TestCase
{
    private TagAwareCacheInterface&MockObject $cache;
    private DashboardCacheListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->listener = new DashboardCacheListener($this->cache);
    }

    #[Test]
    public function itInvalidatesPageMetricsOnPageEvent(): void
    {
        $page = $this->createMock(Page::class);
        $event = new PostPersistEventArgs($page, $this->createMock(EntityManagerInterface::class));

        $this->cache
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['page_metrics']);

        ($this->listener)($event);
    }

    #[Test]
    public function itInvalidatesImageMetricsOnImageEvent(): void
    {
        $image = $this->createMock(Image::class);
        $event = new PostUpdateEventArgs($image, $this->createMock(EntityManagerInterface::class));

        $this->cache
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['image_metrics']);

        ($this->listener)($event);
    }

    #[Test]
    public function itDoesNothingForUnhandledEntityTypes(): void
    {
        $unhandledEntity = new \stdClass();
        $event = new PostRemoveEventArgs($unhandledEntity, $this->createMock(EntityManagerInterface::class));

        $this->cache
            ->expects(self::never())
            ->method('invalidateTags');

        ($this->listener)($event);
    }
}
