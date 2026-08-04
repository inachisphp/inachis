<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\RevisionRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Service\Formatting\UrlNormaliser;
use Inachis\Service\Waste\WasteManagerService;
use Symfony\Bundle\SecurityBundle\Security;

readonly class PageBulkActionService
{
    public function __construct(
        private PageRepository $pageRepository,
        private RevisionRepository $revisionRepository,
        private UrlRepository $urlRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private WasteManagerService $wasteManagerService,
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
            /** @var Page|null $post */
            $post = $this->pageRepository->findOneBy(['id' => $id]);
            if (!$post || !$post->getId()) {
                continue;
            }
            match ($action) {
                'delete' => $this->delete($post),
                'private' => $post->setVisible(false),
                'public' => $post->setVisible(true),
                'rebuild' => $post = $this->rebuild($post),
                default => null,
            };
            if ('delete' !== $action) {
                $post->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($post);
                if ('private' === $action || 'public' === $action) {
                    $revision = $this->revisionRepository->hydrateNewRevisionFromPage($post);
                    $revision = $revision
                        ->setContent('')
                        ->setAction(sprintf(
                            RevisionRepository::VISIBILITY_CHANGE,
                            $post->isVisible(),
                        ));
                    $this->entityManager->persist($revision);
                }
            }
            ++$count;
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * @throws \Exception
     */
    public function delete(Page $post): void
    {
        $this->wasteManagerService->sendToWaste($post);
        /** @var \Inachis\Entity\User\User */
        $deletedBy = $this->security->getUser();
        $this->revisionRepository->deleteAndRecordByPage($post, $deletedBy);
        $this->pageRepository->remove($post);
    }

    /**
     * @throws \Exception
     */
    public function rebuild(Page $post): Page
    {
        if (!$post->getUrls()->isEmpty()) {
            foreach ($post->getUrls() as $url) {
                $this->urlRepository->remove($url);
            }
        }
        $title = $post->getTitle();
        // if ($title === null) {
        //     throw new Exception('Page title cannot be null');
        // }
        $link = $post->getPostDateAsLink().'/'.UrlNormaliser::toUri($title);
        $subTitle = $post->getSubTitle();
        if (null !== $subTitle) {
            $link .= '-'.UrlNormaliser::toUri($subTitle);
        }
        $url = new Url($post, $link);
        $this->entityManager->persist($url);
        $post->setUpdatedAt(new \DateTimeImmutable('now'));

        return $post;
    }
}
