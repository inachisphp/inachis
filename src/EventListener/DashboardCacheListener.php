<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Media\Image;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class DashboardCacheListener
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    ) {}

    public function __invoke(
        PostPersistEventArgs|PostUpdateEventArgs|PostRemoveEventArgs $args
    ): void {
        $entity = $args->getObject();

        match (true) {
            $entity instanceof Page => $this->invalidatePageMetrics(),
            $entity instanceof Image => $this->invalidateImageMetrics(),
            default => null,
        };
    }

    private function invalidatePageMetrics(): void
    {
        $this->cache->invalidateTags(['page_metrics']);
    }

    private function invalidateImageMetrics(): void
    {
        $this->cache->invalidateTags(['image_metrics']);
    }
}