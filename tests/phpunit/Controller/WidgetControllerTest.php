<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Inachis\Controller\WidgetController;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

abstract class TestPageRepository extends EntityRepository
{
    abstract public function getPagesWithCategory(Category $category, int $maxDisplayCount = 0): array;
}

final class WidgetControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ContainerInterface&MockObject $container;
    private Environment&MockObject $twig;
    private WidgetController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->container->method('has')->willReturnCallback(
            static fn (string $id): bool => 'twig' === $id
        );
        $this->container->method('get')->willReturnCallback(
            fn (string $id): object => match ($id) {
                'twig' => $this->twig,
            }
        );

        $this->controller = new WidgetController($this->entityManager);
        $this->controller->setContainer($this->container);
    }

    #[Test]
    public function itReturnsRecentTrips(): void
    {
        $series = new Series();
        $seriesRepository = $this->createMock(EntityRepository::class);
        $seriesRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['visible' => 1], ['lastDate' => 'DESC'], 5)
            ->willReturn([$series]);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Series::class)
            ->willReturn($seriesRepository);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('web/partials/recent_trips.html.twig', [
                'trips' => [$series],
            ])
            ->willReturn('<div>Trips Widget</div>');

        $response = $this->controller->getRecentTrips(5);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<div>Trips Widget</div>', $response->getContent());
    }

    #[Test]
    public function itReturnsRecentRunningWhenCategoryExists(): void
    {
        $category = new Category();
        $category->setTitle('Running');

        $page = new Page();

        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['title' => 'Running'])
            ->willReturn($category);

        $pageRepository = $this->createMock(TestPageRepository::class);
        $pageRepository
            ->expects(self::once())
            ->method('getPagesWithCategory')
            ->with($category, 10)
            ->willReturn([$page]);

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Category::class, $categoryRepository],
                [Page::class, $pageRepository],
            ]);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('web/partials/recent_running.html.twig', [
                'races' => [$page],
            ])
            ->willReturn('<div>Running Widget</div>');

        $response = $this->controller->getRecentRunning();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<div>Running Widget</div>', $response->getContent());
    }

    #[Test]
    public function itReturnsRecentRunningWhenCategoryDoesNotExist(): void
    {
        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['title' => 'Running'])
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('web/partials/recent_running.html.twig', [
                'races' => [],
            ])
            ->willReturn('<div>Empty Running Widget</div>');

        $response = $this->controller->getRecentRunning(5);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<div>Empty Running Widget</div>', $response->getContent());
    }

    #[Test]
    public function itReturnsRecentArticlesWhenCategoryExists(): void
    {
        $category = new Category();
        $category->setTitle('Articles');

        $page = new Page();

        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['title' => 'Articles'])
            ->willReturn($category);

        $pageRepository = $this->createMock(TestPageRepository::class);
        $pageRepository
            ->expects(self::once())
            ->method('getPagesWithCategory')
            ->with($category, 10)
            ->willReturn([$page]);

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Category::class, $categoryRepository],
                [Page::class, $pageRepository],
            ]);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('web/partials/recent_articles.html.twig', [
                'articles' => [$page],
            ])
            ->willReturn('<div>Articles Widget</div>');

        $response = $this->controller->getRecentArticles();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<div>Articles Widget</div>', $response->getContent());
    }

    #[Test]
    public function itReturnsRecentArticlesWhenCategoryDoesNotExist(): void
    {
        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['title' => 'Articles'])
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('web/partials/recent_articles.html.twig', [
                'articles' => [],
            ])
            ->willReturn('<div>Empty Articles Widget</div>');

        $response = $this->controller->getRecentArticles(3);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<div>Empty Articles Widget</div>', $response->getContent());
    }
}
