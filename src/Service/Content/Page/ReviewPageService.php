<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\{Page, ReviewComment, ReviewThread};
use Inachis\Entity\User\User;
use Inachis\Repository\Content\ReviewThreadRepository;

class ReviewPageService
{
    /**
     * Constructor for the review page service
     *
     * @param EntityManagerInterface $entityManager
     * @param ReviewThreadRepository $reviewThreadRepository
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewThreadRepository $reviewThreadRepository
    ) {}

    /**
     * Creates a new review thread and initial comment.
     *
     * @param Page $page
     * @param User $author
     * @param string $message
     * @param int $startOffset
     * @param int $endOffset
     * @param string $selectedText
     * @param string $contextBefore
     * @param string $contextAfter
     * @return ReviewThread
     */
    public function createThread(
        Page $page,
        User $author,
        string $message,
        int $startOffset,
        int $endOffset,
        string $selectedText,
        string $contextBefore = '',
        string $contextAfter = '',
    ): ReviewThread {
        $thread = new ReviewThread();

        $thread
            ->setPage($page)
            ->setCreatedBy($author)
            ->setStartOffset($startOffset)
            ->setEndOffset($endOffset)
            ->setSelectedText($selectedText)
            ->setContextBefore($contextBefore)
            ->setContextAfter($contextAfter)
            ->setCreated(new DateTimeImmutable())
            ->setUpdated(new DateTimeImmutable());

        $comment = new ReviewComment();

        $comment
            ->setThread($thread)
            ->setAuthor($author)
            ->setMessage($message)
            ->setCreated(new DateTimeImmutable());

        $this->entityManager->persist($thread);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $thread;
    }

    /**
     * Adds a reply to an existing review thread.
     */
    public function addReply(
        ReviewThread $thread,
        User $author,
        string $message
    ): ReviewComment {
        $comment = new ReviewComment();

        $comment
            ->setThread($thread)
            ->setAuthor($author)
            ->setMessage($message)
            ->setCreated(new DateTimeImmutable());

        $thread->setUpdated(new DateTimeImmutable());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    /**
     * Marks a review thread as resolved.
     */
    public function resolveThread(
        ReviewThread $thread,
        User $resolvedBy
    ): ReviewThread {
        $thread
            ->setResolved(true)
            ->setResolvedBy($resolvedBy)
            ->setResolvedAt(new DateTimeImmutable())
            ->setUpdated(new DateTimeImmutable());

        $this->entityManager->flush();

        return $thread;
    }

    /**
     * Reopens a resolved review thread.
     */
    public function reopenThread(
        ReviewThread $thread
    ): ReviewThread {
        $thread
            ->setResolved(false)
            ->setResolvedBy(null)
            ->setResolvedAt(null)
            ->setUpdated(new DateTimeImmutable());

        $this->entityManager->flush();

        return $thread;
    }

    /**
     * Returns all open review threads for a page.
     *
     * @return array<ReviewThread>
     */
    public function getOpenThreadsForPage(Page $page): array
    {
        return $this->reviewThreadRepository->findBy(
            [
                'page' => $page,
                'resolved' => false
            ],
            [
                'updated' => 'DESC'
            ]
        );
    }

    /**
     * Returns all review threads for a page.
     *
     * @return array<ReviewThread>
     */
    public function getThreadsForPage(Page $page): array
    {
        return $this->reviewThreadRepository->findBy(
            ['page' => $page],
            ['updated' => 'DESC']
        );
    }
}
