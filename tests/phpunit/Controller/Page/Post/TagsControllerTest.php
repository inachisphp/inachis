<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Post;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Controller\Page\Post\TagsController;
use Inachis\Entity\Content\Tag;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\TagRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TagsControllerTest extends InachisControllerTestCase
{
    private function makeController(): TagsController
    {
        return new TagsController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
            $this->wasteRepository,
        );
    }

    public function testGetTagManagerListContentReturnsEmptyList(): void
    {
        $controller = $this->makeController();

        $request = new Request([], [
            'q' => 'test',
        ]);

        $repository = $this->createMock(TagRepository::class);

        $repository
            ->expects($this->once())
            ->method('findByTitleLike')
            ->with('test')
            ->willReturn($this->createMockPaginator([]));

        $response = $controller->getTagManagerListContent(
            $request,
            $repository,
        );

        $data = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([], $data['items']);
        $this->assertSame(0, $data['totalCount']);
    }

    public function testGetTagManagerListContentDeduplicatesTags(): void
    {
        $controller = $this->makeController();

        $request = new Request([], [
            'q' => 'foo',
        ]);

        $tag1 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid4(),
            'getTitle' => 'Foo',
        ]);

        $tag2 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid4(),
            'getTitle' => 'Foo',
        ]);

        $tag3 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid4(),
            'getTitle' => 'Bar',
        ]);

        $repository = $this->createMock(TagRepository::class);

        $repository
            ->expects($this->once())
            ->method('findByTitleLike')
            ->willReturn(
                $this->createMockPaginator([
                    $tag1,
                    $tag2,
                    $tag3,
                ]),
            );

        $response = $controller->getTagManagerListContent(
            $request,
            $repository,
        );

        $data = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $titles = array_column($data['items'], 'text');
        sort($titles);

        $this->assertCount(2, $data['items']);
        $this->assertSame(['Bar', 'Foo'], $titles);
        $this->assertSame(2, $data['totalCount']);
    }

    public function testMergeTagsReturnsBadRequestWhenTargetMissing(): void
    {
        $controller = $this->makeController();

        $request = new Request([], []);

        $response = $controller->mergeTags(
            $request,
            $this->createMock(PageRepository::class),
            $this->createMock(TagRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testMergeTagsReturnsNotFoundWhenTargetDoesNotExist(): void
    {
        $controller = $this->makeController();

        $request = new Request([], [
            'target' => Uuid::uuid4()->toString(),
            'sources' => [
                Uuid::uuid4()->toString(),
            ],
        ]);

        $repository = $this->createMock(TagRepository::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $response = $controller->mergeTags(
            $request,
            $this->createMock(PageRepository::class),
            $repository,
            $this->createMock(EntityManagerInterface::class),
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testMergeTagsReturnsOkWhenNothingNeedsMoving(): void
    {
        $controller = $this->makeController();

        $target = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid4(),
        ]);

        $request = new Request([], [
            'target' => 'target',
            'sources' => [],
        ]);

        $response = $controller->mergeTags(
            $request,
            $this->createMock(PageRepository::class),
            $this->createMock(TagRepository::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    private function createMockPaginator(array $items): Paginator
    {
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIterator',
                'count',
            ])
            ->getMock();

        $paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        return $paginator;
    }
}
