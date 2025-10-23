<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Post;

use App\Controller\AbstractInachisController;
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Form\PostType;
use App\Repository\RevisionRepository;
use App\Util\ContentRevisionCompare;
use App\Util\ReadingTime;
use DateTime;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PageWebController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param int $year
     * @param int $month
     * @param int $day
     * @param string $title
     * @return Response
     * @throws NotFoundHttpException
     */
    #[Route(
        "/{year}/{month}/{day}/{title}",
        requirements: [
            "year" => "\d+",
            "month" => "\d+",
            "day" => "\d+"
        ],
        methods: ["GET" ]
    )]
    public function getPost(Request $request, int $year, int $month, int $day, string $title): Response
    {
        $url = $this->entityManager->getRepository(Url::class)->findOneByLink(
            ltrim(strtok($request->getRequestUri(), '?'), '/')
        );
        if (empty($url)) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    ltrim($request->getRequestUri(), '/')
                )
            );
        }
        if (
            ($url->getContent()->isScheduledPage() || $url->getContent()->isDraft())
            && !$this->security->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            return $this->redirectToRoute(
                'app_default_homepage',
                []
            );
        }
        if (!$url->isDefault()) {
            $url = $this->entityManager->getRepository(Url::class)->getDefaultUrl($url->getContent());
            if (!empty($url) && $url->isDefault()) {
                return new RedirectResponse('/' . $url->getLink(), Response::HTTP_PERMANENTLY_REDIRECT);
            }
        }
        $this->data['post'] = $url->getContent();
        $this->data['url'] = $url->getLink();
        $this->data['textStats'] = ReadingTime::getWordCountAndReadingTime($this->data['post']->getContent());

        $series = $this->entityManager->getRepository(Series::class)->getPublishedSeriesByPost($this->data['post']);
        if (!empty($series)) {
            $postIndex = $series->getItems()->indexOf($this->data['post']);
            $this->data['series'] = [
                'title' => $series->getTitle(),
                'subTitle' => $series->getSubTitle()
            ];
            if (!empty($series->getItems())) {
                if ($postIndex - 1 >= 0) {
                    $this->data['series']['previous'] = $series->getItems()->get($postIndex - 1);
                }
                if ($postIndex + 1 < $series->getItems()->count()) {
                    $this->data['series']['next'] = $series->getItems()->get($postIndex + 1);
                }
            }
        }
        $crawlerDetect = new CrawlerDetect();
        if (!$crawlerDetect->isCrawler()) {
            // @todo record page hit by day
        }
        return $this->render('web/post.html.twig', $this->data);
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
        methods: [ "GET" ])
    ]
    public function getPage(Request $request): Response
    {
        return $this->getPost($request, 0, 0, 0, '');
    }

    /**
     * @param Request $request
     * @param string $tagName
     * @return Response
     */
    #[Route("/tag/{tagName}", methods: [ "GET" ])]
    public function getPostsByTag(Request $request, string $tagName): Response
    {
        $tag = $this->entityManager->getRepository(Tag::class)->findOneByTitle($tagName);

        if (!$tag instanceof Tag) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $tagName
                )
            );
        }
        $this->data['filterName'] = 'tag';
        $this->data['filterValue'] = $tagName;
        $this->data['content'] = $this->entityManager->getRepository(Page::class)->getPagesWithTag($tag);

        return $this->render('web/homepage.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param string $categoryName
     * @return Response
     */
    #[Route("/category/{categoryName}", methods: [ "GET" ])]
    public function getPostsByCategory(Request $request, string $categoryName): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneByTitle($categoryName);
        if (!$category instanceof Category) {
            throw new NotFoundHttpException(
                sprintf(
                    '%s does not exist',
                    $categoryName
                )
            );
        }
        $this->data['filterName'] = 'category';
        $this->data['filterValue'] = $categoryName;
        $this->data['content'] = $this->entityManager->getRepository(Page::class)->getPagesWithCategory($category);

        return $this->render('web/homepage.html.twig', $this->data);
    }
}