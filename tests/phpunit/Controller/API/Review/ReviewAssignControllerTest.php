<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Review;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\API\Review\ReviewAssignController;
use Inachis\Entity\Content\ReviewThread;
use Inachis\Entity\User\User;
use Inachis\Repository\Content\ReviewThreadRepository;
use Inachis\Repository\User\UserRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewAssignControllerTest extends TestCase
{
    public function testAssignAssignsThreadAndReturnsSuccess(): void
    {
        $thread = $this->createMock(ReviewThread::class);
        $user = $this->createStub(User::class);

        $threads = $this->createMock(ReviewThreadRepository::class);
        $threads->expects($this->once())
            ->method('find')
            ->with('thread-id')
            ->willReturn($thread);

        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('find')
            ->with('user-id')
            ->willReturn($user);

        $thread->expects($this->once())
            ->method('setAssignedTo')
            ->with($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('flush');

        $request = new Request(
            content: json_encode([
                'userId' => 'user-id',
            ])
        );

        $controller = $this->getMockBuilder(ReviewAssignController::class)
            ->onlyMethods(['json'])
            ->getMock();
        $controller->expects($this->once())
            ->method('json')
            ->with(['success' => true])
            ->willReturn(new JsonResponse(['success' => true]));

        $response = $controller->assign(
            'thread-id',
            $request,
            $threads,
            $users,
            $entityManager
        );

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertSame(
            ['success' => true],
            $data
        );
    }

    public function testAssignThrowsWhenThreadNotFound(): void
    {
        $threads = $this->createMock(ReviewThreadRepository::class);
        $threads->expects($this->once())
            ->method('find')
            ->with('thread-id')
            ->willReturn(null);

        $users = $this->createStub(UserRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $request = new Request();
        $controller = new ReviewAssignController();

        $this->expectException(NotFoundHttpException::class);
        $controller->assign(
            'thread-id',
            $request,
            $threads,
            $users,
            $entityManager
        );
    }

    public function testAssignThrowsWhenUserNotFound(): void
    {
        $thread = $this->createStub(ReviewThread::class);

        $threads = $this->createMock(ReviewThreadRepository::class);
        $threads->expects($this->once())->method('find')->willReturn($thread);

        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('find')
            ->with('user-id')
            ->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $request = new Request(
            content: json_encode([
                'userId' => 'user-id',
            ])
        );

        $controller = new ReviewAssignController();

        $this->expectException(NotFoundHttpException::class);

        $controller->assign(
            'thread-id',
            $request,
            $threads,
            $users,
            $entityManager
        );
    }

    public function testReviewersReturnsActiveUsers(): void
    {
        $uuid = Uuid::uuid1();
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn($uuid);
        $user->expects($this->once())->method('getDisplayName')->willReturn('David');

        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('findBy')
            ->with([
                'isRemoved' => false,
                'isActive' => true,
            ])
            ->willReturn([$user]);

        $expected = [
            [
                'id' => $uuid,
                'name' => 'David',
            ],
        ];
        $controller = $this->getMockBuilder(ReviewAssignController::class)
            ->onlyMethods(['json'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with($expected)
            ->willReturn(new JsonResponse($expected));

        $response = $controller->reviewers($users);
        $data = json_decode($response->getContent(), true);

        $this->assertSame([
            [
                'id' => $uuid->toString(),
                'name' => 'David',
            ],
        ], $data);
    }

    public function testReviewersReturnsEmptyArrayWhenNoUsersFound(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('findBy')
            ->with([
                'isRemoved' => false,
                'isActive' => true,
            ])
            ->willReturn([]);

        $controller = $this->getMockBuilder(ReviewAssignController::class)
            ->onlyMethods(['json'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with([])
            ->willReturn(new JsonResponse([]));

        $response = $controller->reviewers($users);

        $this->assertSame(
            [],
            json_decode($response->getContent(), true)
        );
    }
}
