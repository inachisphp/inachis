<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Inachis\Entity\Content\Page;
use Inachis\Repository\Media\ImageRepository;

/**
 * Event subscriber to calculate and set the total size of images
 * used within the content of a Page.
 */
class PageImageSizeListener implements EventSubscriber
{
    private ImageRepository $imageRepository;

    public function __construct(ImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->calculateImageSize($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->calculateImageSize($args->getObject());
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    private function calculateImageSize(object $entity): void
    {
        if (!$entity instanceof Page) {
            return;
        }

        $content = $entity->getContent() ?? '';
        $totalSize = 0;

        // Find all markdown /imgs/ references, e.g. ![alt](/imgs/filename.ext)
        if (preg_match_all('/\/imgs\/([a-zA-Z0-9_\-\.]+)/', $content, $matches)) {
            $filenames = array_unique($matches[1]);

            if (!empty($filenames)) {
                $images = $this->imageRepository->findBy(['filename' => $filenames]);
                foreach ($images as $image) {
                    $totalSize += $image->getFilesize();
                }
            }
        }

        // Also include the feature image size if there is one
        if (null !== $entity->getFeatureImage()) {
            $totalSize += $entity->getFeatureImage()->getFilesize();
        }

        $entity->setImageSize($totalSize);
    }
}
