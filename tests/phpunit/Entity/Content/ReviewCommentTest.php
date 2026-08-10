<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Content;

use Inachis\Entity\Content\ReviewComment;
use Inachis\Entity\Content\ReviewThread;
use Inachis\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ReviewCommentTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $thread = $this->createReviewThread();
        $author = $this->createUser();
        $message = 'This is a test comment';

        $comment = new ReviewComment($thread, $author, $message);

        $this->assertNull($comment->getId());
        $this->assertSame($thread, $comment->getThread());
        $this->assertSame($author, $comment->getAuthor());
        $this->assertSame($message, $comment->getMessage());
    }

    public function testSetters(): void
    {
        $initialThread = $this->createReviewThread();
        $initialAuthor = $this->createUser();
        $comment = new ReviewComment($initialThread, $initialAuthor, 'Initial message');

        $newThread = $this->createReviewThread();
        $newAuthor = $this->createUser();
        $newMessage = 'Updated message';
        $created = new \DateTimeImmutable('2026-01-01 10:00:00');
        $updated = new \DateTimeImmutable('2026-01-02 12:00:00');

        $this->assertSame($comment, $comment->setThread($newThread));
        $this->assertSame($comment, $comment->setAuthor($newAuthor));
        $this->assertSame($comment, $comment->setMessage($newMessage));
        $this->assertSame($comment, $comment->setCreated($created));
        $this->assertSame($comment, $comment->setUpdated($updated));

        $this->assertSame($newThread, $comment->getThread());
        $this->assertSame($newAuthor, $comment->getAuthor());
        $this->assertSame($newMessage, $comment->getMessage());
        $this->assertSame($created, $comment->getCreated());
        $this->assertSame($updated, $comment->getUpdated());
    }

    public function testPrePersistSetsCreatedAndUpdated(): void
    {
        $thread = $this->createReviewThread();
        $author = $this->createUser();
        $comment = new ReviewComment($thread, $author, 'Test');

        $before = new \DateTimeImmutable();
        $comment->prePersist();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $comment->getCreated());
        $this->assertLessThanOrEqual($after, $comment->getCreated());
        $this->assertGreaterThanOrEqual($before, $comment->getUpdated());
        $this->assertLessThanOrEqual($after, $comment->getUpdated());
    }

    public function testPreUpdateUpdatesUpdatedTimestamp(): void
    {
        $thread = $this->createReviewThread();
        $author = $this->createUser();
        $comment = new ReviewComment($thread, $author, 'Test');

        $initialTime = new \DateTimeImmutable('2026-01-01 00:00:00');
        $comment->setUpdated($initialTime);

        $before = new \DateTimeImmutable();
        $comment->preUpdate();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $comment->getUpdated());
        $this->assertLessThanOrEqual($after, $comment->getUpdated());
    }

    public function testIdCanBeSetViaReflection(): void
    {
        $thread = $this->createReviewThread();
        $author = $this->createUser();
        $comment = new ReviewComment($thread, $author, 'Test');

        $uuid = Uuid::uuid4();
        $reflection = new \ReflectionClass($comment);
        $property = $reflection->getProperty('id');
        $property->setValue($comment, $uuid);

        $this->assertSame($uuid, $comment->getId());
    }

    private function createReviewThread(): ReviewThread
    {
        $reflection = new \ReflectionClass(ReviewThread::class);

        if ($reflection->isFinal()) {
            return $reflection->newInstanceWithoutConstructor();
        }

        return $this->createStub(ReviewThread::class);
    }

    private function createUser(): User
    {
        $reflection = new \ReflectionClass(User::class);

        if ($reflection->isFinal()) {
            return $reflection->newInstanceWithoutConstructor();
        }

        return $this->createStub(User::class);
    }
}
