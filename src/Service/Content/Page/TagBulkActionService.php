<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\{Tag};
use Inachis\Repository\Content\{PageRepository,TagRepository};
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class TagBulkActionService
{
    /**
     * @param PageRepository $pageRepository
     * @param TagRepository $tagRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private PageRepository $pageRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Applies a bulk action to pages
     * 
     * @param string $action
     * @param array<string> $ids
     * @return int
     * @throws Exception
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
                'delete'  => $this->delete($tag),
                default   => null,
            };
            $count++;
        }
        $this->entityManager->flush();
        return $count;
    }

    /**
     * @param Tag $tag
     * @throws Exception
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
