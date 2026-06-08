<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page;

use Inachis\Entity\Content\{Page, Url};
use Inachis\Repository\Content\{PageRepository, RevisionRepository, UrlRepository};
use Inachis\Util\UrlNormaliser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Inachis\Service\Waste\WasteManagerService;
use DateTimeImmutable;
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
        private Security $security,
        private WasteManagerService $wasteManagerService,
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
            /** @var Page|null $post */
            $post = $this->pageRepository->findOneBy(['id' => $id]);
            if (!$post || !$post->getId()) {
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
                $post->setModDate(new DateTimeImmutable());
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
     * @param Page $post
     * @throws Exception
     */
    public function delete(Page $post): void
    {
        $this->wasteManagerService->sendToWaste($post);
        $this->revisionRepository->deleteAndRecordByPage($post, $this->security->getUser());
        $this->pageRepository->remove($post);
    }

    /**
     * @param Page $post
     * @return Page
     * @throws Exception
     */
    public function rebuild(Page $post): Page
    {
        if (!$post->getUrls()->isEmpty()) {
            foreach ($post->getUrls() as $url) {
                $this->urlRepository->remove($url);
            }
        }
        $title = $post->getTitle();
        if ($title === null) {
            throw new Exception('Page title cannot be null');
        }
        $link = $post->getPostDateAsLink() . '/' . UrlNormaliser::toUri($title);
        $subTitle = $post->getSubTitle();
        if ($subTitle !== null) {
            $link .= '-' . UrlNormaliser::toUri($subTitle);
        }
        $url = new Url($post, $link);
        $this->entityManager->persist($url);
        $post->setModDate(new DateTimeImmutable('now'));

        return $post;
    }
}
