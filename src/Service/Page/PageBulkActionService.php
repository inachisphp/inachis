<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page;

use Inachis\Entity\Page;
use Inachis\Entity\Url;
use Inachis\Repository\PageRepository;
use Inachis\Repository\RevisionRepository;
use Inachis\Repository\UrlRepository;
use Inachis\Util\UrlNormaliser;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class PageBulkActionService
{
    /**
     * @param PageRepository $pageRepository
     * @param RevisionRepository $revisionRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private PageRepository $pageRepository,
        private RevisionRepository $revisionRepository,
        private UrlRepository $urlRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param string $action
     * @param array $ids
     * @return int
     * @throws Exception
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            $post = $this->pageRepository->findOneBy(['id' => $id]);
            if (empty($post->getId())) {
                continue;
            }
            match ($action) {
                'delete'  => $this->delete($post),
                'private'  => $post->setVisibility(Page::PRIVATE),
                'public' => $post->setVisibility(Page::PUBLIC),
                'rebuild' => $post = $this->rebuild($post),
                default   => null,
            };
            if ($action !== 'delete') {
                $post->setModDate(new DateTime());
                $this->entityManager->persist($post);
                if ($action === 'private' || $action === 'public') {
                    $revision = $this->revisionRepository->hydrateNewRevisionFromPage($post);
                    $revision = $revision
                        ->setContent('')
                        ->setAction(sprintf(
                            RevisionRepository::VISIBILITY_CHANGE,
                            $post->getVisibility()
                        ));
                    $this->entityManager->persist($revision);
                }
            }
            $count++;
        }
        $this->entityManager->flush();
        return $count;
    }

    /**
     * @throws Exception
     */
    public function delete(Page $post): void
    {
        $this->revisionRepository->deleteAndRecordByPage($post);
        $this->pageRepository->remove($post);
    }

    /**
     * @throws Exception
     */
    public function rebuild(Page $post): Page
    {
        if (!empty($post->getUrls())) {
            foreach ($post->getUrls() as $url) {
                $this->urlRepository->remove($url);
            }
        }
        $link = $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($post->getTitle());
        if ($post->getSubTitle() !== null) {
            $link .= '-' . UrlNormaliser::toUri($post->getSubTitle());
        }
        $url = new Url($post, $link);
        $this->entityManager->persist($url);
        $post->setModDate(new DateTime('now'));

        return $post;
    }
}
