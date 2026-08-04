<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Tag;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\TagRepository;

readonly class TagBulkActionService
{
    public function __construct(
        private PageRepository $pageRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Applies a bulk action to pages.
     *
     * @param array<string> $ids
     *
     * @throws \Exception
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            /** @var Tag|null */
            $tag = $this->tagRepository->findOneBy(['id' => $id]);
            if (!$tag || !$tag->getId()) {
                continue;
            }
            match ($action) {
                'delete' => $this->delete($tag),
                default => null,
            };
            ++$count;
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * @throws \Exception
     */
    public function delete(Tag $tag): void
    {
        $pages = $this->pageRepository->getFilteredOfTypeByPostDate(['tags' => [$tag->getId()?->toString() ?? '']], '*', 0, 0);
        if ($pages->getIterator()->count() > 0) {
            throw new \Exception(sprintf('Tag \'%s\' still in use - please remove tag from pages before deleting', $tag->getTitle()));
        }

        $this->entityManager->remove($tag);
    }
}
