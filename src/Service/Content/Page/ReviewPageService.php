<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\ReviewComment;
use Inachis\Entity\Content\ReviewThread;
use Inachis\Entity\User\User;
use Inachis\Repository\Content\ReviewThreadRepository;

class ReviewPageService
{
    /**
     * Constructor for the review page service.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewThreadRepository $reviewThreadRepository,
    ) {
    }

    /**
     * Creates a new review thread and initial comment.
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
            ->setCreated(new \DateTimeImmutable())
            ->setUpdated(new \DateTimeImmutable());

        $comment = new ReviewComment($thread, $author, $message);

        $comment
            ->setThread($thread)
            ->setAuthor($author)
            ->setMessage($message)
            ->setCreated(new \DateTimeImmutable());

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
        string $message,
    ): ReviewComment {
        $comment = new ReviewComment($thread, $author, $message);

        $comment
            ->setThread($thread)
            ->setAuthor($author)
            ->setMessage($message)
            ->setCreated(new \DateTimeImmutable());

        $thread->setUpdated(new \DateTimeImmutable());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    /**
     * Marks a review thread as resolved.
     */
    public function resolveThread(
        ReviewThread $thread,
        User $resolvedBy,
    ): ReviewThread {
        $thread->resolve($resolvedBy)->setUpdated(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $thread;
    }

    /**
     * Reopens a resolved review thread.
     */
    public function reopenThread(
        ReviewThread $thread,
    ): ReviewThread {
        $thread->reopen()->setUpdated(new \DateTimeImmutable());
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
                'resolved' => false,
            ],
            [
                'updated' => 'DESC',
            ],
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
            ['updated' => 'DESC'],
        );
    }
}
