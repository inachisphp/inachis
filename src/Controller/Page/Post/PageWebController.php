<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use Exception;
use Inachis\Controller\AbstractWebController;
use Inachis\Entity\Content\{Category,Page,Series,Tag,Url};
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Content\UrlRepository;
use Inachis\Service\Content\ReadingTime;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PageWebController extends AbstractWebController
{
    /**
     * @param string $year
     * @param string $month
     * @param string $day
     * @param string $title
     * @return Response
     * @throws NotFoundHttpException|Exception
     */
    #[Route(
        "/{year}/{month}/{day}/{title}",
        requirements: [
            "year" => "\d{4}",
            "month" => "\d{2}",
            "day" => "\d{2}"
        ],
        methods: ["GET" ]
    )]
    #[Route(
        "/incc/preview/{year}/{month}/{day}/{title}",
        requirements: [
            "year" => "\d{4}",
            "month" => "\d{2}",
            "day" => "\d{2}"
        ],
        methods: ["GET" ]
    )]
    public function getPost(
        string $year,
        string $month,
        string $day,
        string $title,
        SeriesRepository $seriesRepository,
        UrlRepository $urlRepository
    ): Response {
        $link = sprintf('%d/%02d/%02d/%s', $year, $month, $day, $title);

        return $this->renderPostOrPage($link, $seriesRepository, $urlRepository);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(
        "/{page}",
        requirements: [
            'page' => '^(?!setup$)(?!\d{4}-[a-zA-Z\-]+$)[^/]+$'
        ],
        methods: [ "GET" ],
        priority: -100
    )]
    public function getPage(
        Request $request,
        SeriesRepository $seriesRepository,
        UrlRepository $urlRepository,
    ): Response {
        $link = $request->attributes->getString('page');

        return $this->renderPostOrPage($link, $seriesRepository, $urlRepository);
    }

    /**
     * Outputs a page of all pages/posts with the specific tag
     * @param string $tagName
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("/tag/{tagName}", methods: [ "GET" ])]
    public function getPostsByTag(
        string $tagName,
        PageRepository $pageRepository,
    ): Response {
        // TODO Change this to use slug
        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['title' => $tagName]);

        if (!$tag instanceof Tag) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $tagName
                )
            );
        }

        return $this->render('web/pages/homepage.html.twig', [
            'viewModel' => $this->viewModel,
            'filterName' => 'tag',
            'filterValue' => $tagName,
            'content' => $pageRepository->getLiveContentWithTag($tag),
        ]);
    }

    /**
     * Outputs a page of all pages/posts with the specific category
     *
     * @param string $categoryName
     * @param CategoryRepository $categoryRepository
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("/category/{categoryName}", methods: [ "GET" ])]
    public function getPostsByCategory(
        string $categoryName,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository
    ): Response {
        // TODO change this to use slug
        $category = $categoryRepository->findOneBy(['title' => $categoryName]);
        if (!$category instanceof Category) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $categoryName
                )
            );
        }
        return $this->render('web/pages/homepage.html.twig', [
            'viewModel' => $this->viewModel,
            'filterName' => 'category',
            'filterValue' => $categoryName,
            'content' => $pageRepository->getLiveContentWithCategory($category),
        ]);
    }

    /**
     * Helper function for rendering posts and pages to avoid code repitition
     *
     * @param string $link
     * @param SeriesRepository $seriesRepository
     * @param UrlRepository $urlRepository
     * @return Response
     */
    protected function renderPostOrPage(
        string $link,
        SeriesRepository $seriesRepository,
        UrlRepository $urlRepository
    ): Response {
        /** @var Url|null */
        $url = $urlRepository->findOneBy([ 'link' => $link ]);
        if (!$url instanceof Url || (
            !$url->isContentLive() && !$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')
        )) {
            throw new NotFoundHttpException(sprintf('%s does not exist', $link));
        }

        if (!$url->isDefault()) {
            /** @var Url|null */
            $url = $urlRepository->getDefaultUrl($url->getContent());
            if ($url instanceof Url) {
                return new RedirectResponse('/' . $url->getLink(), Response::HTTP_PERMANENTLY_REDIRECT);
            }
            throw new NotFoundHttpException(sprintf('%s does not exist', $link));
        }

        /** @var Series|null */
        $series = $seriesRepository->getPublishedSeriesByPost($url->getContent());
        $seriesNav = null;
        if (!empty($series)) {
            /** @var int */
            $postIndex = $series->getItems()->indexOf($url->getContent());
            $seriesNav = [
                'title' => $series->getTitle(),
                'subTitle' => $series->getSubTitle()
            ];
            if ($postIndex - 1 >= 0) {
                $seriesNav['previous'] = $series->getItems()->get($postIndex - 1);
            }
            if ($postIndex + 1 < $series->getItems()->count()) {
                $seriesNav['next'] = $series->getItems()->get($postIndex + 1);
            }
        }
        $crawlerDetect = new CrawlerDetect();
        if (!$crawlerDetect->isCrawler()) {
            // TODO: record page hit by day
        }

        return $this->render('web/pages/post.html.twig', [
            'viewModel' => $this->viewModel,
            'post' => $url->getContent(),
            'series' => $seriesNav,
            'textStats' => ReadingTime::getWordCountAndReadingTime($url->getContent()->getContent()),
            'url' => $url->getLink(),
        ]);
    }
}
