<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller;

use ArrayIterator;
use Inachis\Controller\TagsController;
use Inachis\Entity\Content\Tag;
use Inachis\Repository\TagRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TagsControllerTest extends InachisControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

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
        $request = new Request([], ['q' => 'test']);

        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findByTitleLike')->willReturn($this->createMockPaginator([]));
        $this->entityManager->expects($this->atLeast(0))
            ->method('getRepository')->willReturn($tagRepository);

        $response = $controller->getTagManagerListContent($request, $tagRepository);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([], $data['items']);
        $this->assertSame(0, $data['totalCount']);
    }

    public function testGetTagManagerListContentReturnsDeduplicatedTags(): void
    {
        $controller = $this->makeController();
        $request = new Request([], ['q' => 'abc']);

        $tag1 = $this->createConfiguredStub(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Foo'
        ]);
        $tag2 = $this->createConfiguredStub(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Foo' // duplicate title
        ]);
        $tag3 = $this->createConfiguredStub(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Bar'
        ]);

        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findByTitleLike')
            ->willReturn($this->createMockPaginator([$tag1, $tag2, $tag3]));
        $this->entityManager->expects($this->atLeast(0))
            ->method('getRepository')->willReturn($tagRepository);

        $response = $controller->getTagManagerListContent($request, $tagRepository);
        $data = json_decode($response->getContent(), true, JSON_THROW_ON_ERROR);
        $titles = array_column($data['items'], 'text');
        sort($titles);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertCount(2, $data['items']);
        $this->assertSame(['Bar', 'Foo'], $titles);
        $this->assertSame(2, $data['totalCount']);
    }

    private function createMockPaginator(array $items): Paginator
    {
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator', 'count'])
            ->getMock();
        $paginator->expects($this->once())->method('getIterator')
            ->willReturn(new ArrayIterator($items));
        $paginator->expects($this->atLeast(0))->method('count')
            ->willReturn(count($items));

        return $paginator;
    }
}
