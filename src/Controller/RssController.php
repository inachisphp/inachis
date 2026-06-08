<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Entity\Content\{Category, Page};
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RssController extends AbstractInachisController
{
    /**
     * Helper to log feed subscription requests
     */
    private function logSubscriberHit(Request $request, string $feedPath): void
    {
        $userAgent = $request->headers->get('User-Agent', '');
        $ip = $request->getClientIp() ?? '127.0.0.1';
        $visitorId = hash('sha256', $ip . '|' . $userAgent);

        $dir = $this->params->get('kernel.project_dir') . '/var/analytics';
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
     */
    #[Route('/feed', name: 'rss_feed', methods: ['GET'])]
    public function feed(Request $request, PageRepository $pageRepository): Response
    {
        $this->logSubscriberHit($request, '/feed');

        $paginator = $pageRepository->getFilteredOfTypeByPostDate(
            [
                'status' => EditorialStatus::PUBLISHED,
                'visibility' => Page::PUBLIC,
                'toDate' => new \DateTimeImmutable(),
            ],
            Page::TYPE_POST,
            0,
            20
        );

        $this->data['posts'] = iterator_to_array($paginator);
        $this->data['feed_title'] = $this->data['settings']['siteTitle'];
        $this->data['feed_description'] = $this->data['settings']['abstract'] ?: 'Blog post updates';
        $this->data['feed_url'] = $this->data['settings']['domain'] . '/feed';

        $response = new Response();
        $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');

        return $this->render('web/pages/rss.xml.twig', $this->data, $response);
    }

    /**
     * RSS Feed filtered by category
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
                'status' => EditorialStatus::PUBLISHED,
                'visibility' => Page::PUBLIC,
                'toDate' => new \DateTimeImmutable(),
                'categories' => [$category->getId()->toString()],
            ],
            Page::TYPE_POST,
            0,
            20
        );

        $this->data['posts'] = iterator_to_array($paginator);
        $this->data['feed_title'] = $this->data['settings']['siteTitle'] . ' - ' . $category->getTitle();
        $this->data['feed_description'] = $category->getDescription() ?: 'Posts in category ' . $category->getTitle();
        $this->data['feed_url'] = $this->data['settings']['domain'] . '/feed/' . $category->getTitle();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');

        return $this->render('web/pages/rss.xml.twig', $this->data, $response);
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

        $this->data['page']['title'] = 'Subscribe to RSS Feeds';
        $this->data['page']['description'] = 'Choose from our range of RSS feeds to stay updated with latest articles.';
        $this->data['categories'] = $categories;

        return $this->render('web/pages/feeds-list.html.twig', $this->data);
    }
}
