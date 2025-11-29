<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller;

use App\Controller\TagsController;
use App\Entity\Tag;
use App\Repository\TagRepository;
use ArrayIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TagsControllerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    protected TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    private function makeController(): TagsController
    {
        return new TagsController(
            $this->entityManager,
            $this->security,
            $this->translator,
        );
    }

    public function testGetTagManagerListContentReturnsEmptyList(): void
    {
        $controller = $this->makeController();
        $request = new Request([], ['q' => 'test']);
        $this->security->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY')
            ->willReturn(true);

        $tagRepo = $this->createMock(TagRepository::class);
        $tagRepo->method('findByTitleLike')->willReturn($this->createMockPaginator([]));
        $this->entityManager->method('getRepository')->willReturn($tagRepo);

        $response = $controller->getTagManagerListContent($request, $tagRepo);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([], $data['items']);
        $this->assertSame(0, $data['totalCount']);
    }

    public function testGetTagManagerListContentReturnsDeduplicatedTags(): void
    {
        $controller = $this->makeController();
        $request = new Request([], ['q' => 'abc']);
        $this->security->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY')
            ->willReturn(true);

        $tag1 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Foo'
        ]);
        $tag2 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Foo' // duplicate title
        ]);
        $tag3 = $this->createConfiguredMock(Tag::class, [
            'getId' => Uuid::uuid1(),
            'getTitle' => 'Bar'
        ]);

        $tagRepo = $this->createMock(TagRepository::class);
        $tagRepo->method('findByTitleLike')
            ->willReturn($this->createMockPaginator([$tag1, $tag2, $tag3]));
        $this->entityManager->method('getRepository')->willReturn($tagRepo);

        $response = $controller->getTagManagerListContent($request, $tagRepo);
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

        $paginator->method('getIterator')
            ->willReturn(new ArrayIterator($items));

        $paginator->method('count')
            ->willReturn(count($items));

        return $paginator;
    }
}
