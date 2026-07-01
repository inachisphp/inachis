<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page;

use Inachis\Controller\AbstractWebController;
use Inachis\Entity\Content\Page;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RssController extends AbstractWebController
{
    /**
     * Helper to log feed subscription requests
     * 
     * @param Request $request
     * @param string $feedPath
     */
    private function logSubscriberHit(Request $request, string $feedPath): void
    {
        $userAgent = $request->headers->get('User-Agent', '');
        $ip = $request->getClientIp() ?? '127.0.0.1';
        $visitorId = hash('sha256', $ip . '|' . $userAgent);
        /** @var string */
        $projectDir = $this->params->get('kernel.project_dir');
        $dir = $projectDir . '/var/analytics';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $date = date('Y-m-d');
        $file = sprintf('%s/subscriber-%s.log', $dir, $date);

        $line = json_encode([
            'path' => $feedPath,
            'date' => $date,
            'visitor' => $visitorId,
            'ua' => $userAgent,
            'ts' => time(),
        ], JSON_UNESCAPED_SLASHES);

        file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Default main RSS Feed
     * 
     * @param Request $request
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route('/feed', name: 'rss_feed', methods: ['GET'])]
    public function feed(Request $request, PageRepository $pageRepository): Response
    {
        $this->logSubscriberHit($request, '/feed');

        /** @var \Doctrine\ORM\Tools\Pagination\Paginator<Page> */
        $paginator = $pageRepository->getFilteredOfTypeByPostDate(
            [
                'status' => EditorialStatus::PUBLISHED->value,
                'visible' => true,
                'toDate' => new \DateTimeImmutable(),
            ],
            Page::TYPE_POST,
            20,
            0,
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
        return $this->render('web/pages/rss.xml.twig',
            [
                'viewModel' => $this->viewModel,
                'posts' => iterator_to_array($paginator),
                'feed_title' => $this->viewModel->settings->siteTitle,
                'feed_description' => $this->viewModel->settings->abstract ?: 'Blog post updates',
                'feed_url' => $this->viewModel->settings->domain . '/feed',
            ],
            $response
        );
    }

    /**
     * RSS Feed filtered by category
     * 
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @param PageRepository $pageRepository
     * @param string $categoryName
     * @return Response
     */
    #[Route('/feed/{categoryName}', name: 'rss_feed_category', methods: ['GET'])]
    public function categoryFeed(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository,
        string $categoryName
    ): Response {
        $this->logSubscriberHit($request, '/feed/' . $categoryName);

        $category = $categoryRepository->findOneBy([
            'title' => $categoryName
        ]);

        if (empty($category)) {
            throw new NotFoundHttpException(sprintf('Category %s not found', $categoryName));
        }

        $paginator = $pageRepository->getFilteredOfTypeByPostDate(
            [
                'status' => EditorialStatus::PUBLISHED->value,
                'visible' => true,
                'toDate' => new \DateTimeImmutable(),
                'categories' => [$category->getId()?->toString() ?? ''],
            ],
            Page::TYPE_POST,
            20,
            0,
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
        return $this->render(
            'web/pages/rss.xml.twig', [
                'viewModel' => $this->viewModel,
                'posts' => iterator_to_array($paginator),
                'feed_title' => $this->viewModel->settings->siteTitle . ' - ' . $category->getTitle(),
                'feed_description' => $category->getDescription() ?: 'Posts in category ' . $category->getTitle(),
                'feed_url' => $this->viewModel->settings->domain . '/feed' . $category->getTitle(),
            ],
            $response
        );
    }

    /**
     * Visual list of available RSS Feeds to subscribe to
     * 
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route('/feeds', name: 'rss_feeds_list', methods: ['GET'])]
    public function feedsList(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findBy([
            'visible' => true
        ], ['title' => 'ASC']);

        $this->viewModel->page->title = 'Subscribe to RSS Feeds';
        $this->viewModel->page->description = 'Choose from our range of RSS feeds to stay updated with latest articles.';
        return $this->render('web/pages/feeds-list.html.twig',
        [
            'viewModel' => $this->viewModel,
            'categories' => $categories,
        ]);
    }
}
