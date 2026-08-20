<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Post;

use Doctrine\Common\Collections\ArrayCollection;
use Inachis\Controller\Page\Post\PageWebController;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Content\Tag;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\TagRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageWebControllerTest extends InachisControllerTestCase
{
    private PageWebController $controller;

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new PageWebController(
            $this->entityManager,
            $this->pageViewFactory,
            $this->params,
            $this->security,
            $this->translator,
        );
        $ref = new \ReflectionClass($this->controller);
        foreach (['entityManager', 'security'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setValue($this->controller, $this->$prop);
        }
    }

    public function testGetPostThrowsNotFoundWhenUrlMissing(): void
    {
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPost('2025', '10', '10', 'sample-post', $seriesRepository, $urlRepository);
    }

    public function testGetPostThrowsNotFoundIfContentIsScheduledOrDraft(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('isDraft')->willReturn(false);
        $page->method('isScheduledPage')->willReturn(true);
        $page->method('hasExpired')->willReturn(false);
        $url = $this->createMock(Url::class);
        $url->method('getContent')->willReturn($page);
        $url->method('isContentLive')->willReturn(false);
        $url->method('isDefault')->willReturn(true);
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn($url);
        $seriesRepository = $this->createMock(SeriesRepository::class);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPost('2025', '10', '10', 'sample-post', $seriesRepository, $urlRepository);
    }

    public function testGetPostRedirectsWhenNotDefault(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('isDraft')->willReturn(false);
        $page->method('isScheduledPage')->willReturn(false);
        $page->method('hasExpired')->willReturn(false);
        $url = $this->createMock(Url::class);
        $url->method('isContentLive')->willReturn(true);
        $url->expects($this->once())->method('isDefault')->willReturn(false);
        $url->method('getContent')->willReturn($page);
        $url2 = $this->createMock(Url::class);
        $url2->method('getLink')->willReturn('2025/10/10/sample-post');
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn($url);
        $urlRepository->expects($this->atLeastOnce())
            ->method('getDefaultUrl')
            ->with($page)
            ->willReturn($url2);
        $seriesRepository = $this->createMock(SeriesRepository::class);

        $response = $this->controller->getPost('2025', '10', '10', 'sample-post', $seriesRepository, $urlRepository);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('/2025/10/10/sample-post', $response->headers->get('Location'));
    }

    public function testGetPostRendersTemplate(): void
    {
        $page = $this->createStub(Page::class);
        $page2 = $this->createStub(Page::class);
        $page->method('isDraft')->willReturn(false);
        $page->method('isScheduledPage')->willReturn(false);
        $page->method('hasExpired')->willReturn(false);
        $page->method('getContent')->willReturn('Sample post body');
        $url = $this->createMock(Url::class);
        $url->method('isContentLive')->willReturn(true);
        $url->method('isDefault')->willReturn(true);
        $url->method('getContent')->willReturn($page);
        $url->method('getLink')->willReturn('2025/10/10/sample-post');
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => '2025/10/10/sample-post'])
            ->willReturn($url);
        $seriesByPostResult = $this->createMock(Series::class);
        $seriesByPostResult->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn(new ArrayCollection([$page2, $page, $page2]));
        $seriesByPostResult->method('getTitle')->willReturn('Series title');
        $seriesByPostResult->method('getSubTitle')->willReturn('Series subtitle');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())
            ->method('getPublishedSeriesByPost')
            ->with($page)
            ->willReturn($seriesByPostResult);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->pageViewFactory,
                $this->params,
                $this->security,
                $this->translator,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/post.html.twig')
            ->willReturn(new Response('Rendered OK', 200));

        $response = $controller->getPost('2025', '10', '10', 'sample-post', $seriesRepository, $urlRepository);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPageRendersWithPageAttribute(): void
    {
        $request = new Request();
        $request->attributes->set('page', 'about');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $urlRepository = $this->createMock(UrlRepository::class);
        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->pageViewFactory,
                $this->params,
                $this->security,
                $this->translator,
            ])
            ->onlyMethods(['render'])
            ->getMock();

        $url = $this->createMock(Url::class);
        $page = $this->createMock(Page::class);
        $page->method('getContent')->willReturn('About content');
        $url->method('isContentLive')->willReturn(true);
        $url->method('isDefault')->willReturn(true);
        $url->method('getContent')->willReturn($page);
        $url->method('getLink')->willReturn('about');
        $urlRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['link' => 'about'])
            ->willReturn($url);

        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/post.html.twig')
            ->willReturn(new Response('OK', 200));

        $response = $controller->getPage($request, $seriesRepository, $urlRepository);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('OK', $response->getContent());
    }

    public function testGetPostsByTagThrowsNotFound(): void
    {
        $tagRepository = $this->createMock(TagRepository::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Tag::class)
            ->willReturn($tagRepository);
        $tagRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'nonexistent-tag'])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPostsByTag('nonexistent-tag', $pageRepository);
    }

    /**
     * @throws Exception
     */
    public function testGetPostsByTagRendersTemplate(): void
    {
        $tag = $this->createStub(Tag::class);
        $pages = [$this->createStub(Page::class)];
        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'existing-tag'])
            ->willReturn($tag);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getLiveContentWithTag')
            ->with($tag)
            ->willReturn($pages);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Tag::class)
            ->willReturn($tagRepository);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->pageViewFactory,
                $this->params,
                $this->security,
                $this->translator,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));

        $response = $controller->getPostsByTag('existing-tag', $pageRepository);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPostsByCategoryThrowsNotFound(): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'missing-category'])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->getPostsByCategory('missing-category', $categoryRepository, $pageRepository);
    }

    public function testGetPostsByCategoryRendersTemplate(): void
    {
        $category = $this->createStub(Category::class);
        $pages = [$this->createStub(Page::class)];
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'category-name'])
            ->willReturn($category);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())
            ->method('getLiveContentWithCategory')
            ->with($category)
            ->willReturn($pages);

        $controller = $this->getMockBuilder(PageWebController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->pageViewFactory,
                $this->params,
                $this->security,
                $this->translator,
            ])
            ->onlyMethods(['render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->with('web/pages/homepage.html.twig')
            ->willReturn(new Response('Rendered OK', 200));

        $response = $controller->getPostsByCategory('category-name', $categoryRepository, $pageRepository);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
