<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use Inachis\Repository\{SeriesRepository,UrlRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use SimpleXMLElement;
use DateTime;

class SitemapGenerator
{
    /** @var RouterInterface  */
    private RouterInterface $router;

    /** @var ParameterBagInterface*/
    private ParameterBagInterface $params;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var SeriesRepository */
    private SeriesRepository $seriesRepository;

    /** @var UrlRepository */
    private UrlRepository $urlRepository;

    public function __construct(
        RouterInterface $router,
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        SeriesRepository $seriesRepository,
        UrlRepository $urlRepository
    ) {
        $this->router = $router;
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->seriesRepository = $seriesRepository;
        $this->urlRepository = $urlRepository;
    }

    /**
     * Generates the XML sitemap content.
     */
    public function generate(): string
    {
        $baseUrl = $this->params->has('app.domain') ? $this->params->get('app.domain') : getenv('APP_DOMAIN');
        if (!$baseUrl) {
            throw new \RuntimeException('Base URL not configured. Set app.domain or APP_DOMAIN env var.');
        }
        $baseUrl = rtrim($baseUrl, '/');

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // static routes
        // foreach ($this->router->getRouteCollection() as $name => $route) {
        //     $methods = $route->getMethods();
        //     if (!empty($methods) && !in_array('GET', $methods, true)) {
        //         continue;
        //     }
        //     $path = $route->getPath();
        //     // exclude /incc* paths as per user request
        //     if (preg_match('#^/(incc|feed[s/]|health|setup|robots.txt|{page}|_error|_profiler|_wdt)#', $path)) {
        //         continue;
        //     }
        //     // generate URL
        //     $url = $baseUrl . $path;
        //     $urlElement = $xml->addChild('url');
        //     $urlElement->addChild('loc', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
        //     $urlElement->addChild('lastmod', (new DateTime())->format('Y-m-d'));
        //     $urlElement->addChild('changefreq', 'weekly');
        //     $urlElement->addChild('priority', '0.5');
        // }

        // dynamic URLs from Url entity where default = true
        $defaults = $this->urlRepository->findSitemapUrls();
        foreach ($defaults as $urlEntity) {
            $path = $urlEntity->getPath();

            if (preg_match('#^/incc#', $path)) {
                continue;
            }

            $url = $baseUrl . $path;

            $page = $urlEntity->getContent();

            $urlElement = $xml->addChild('url');
            $urlElement->addChild('loc', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
            $urlElement->addChild(
                'lastmod',
                $page->getModDate()->format('Y-m-d')
            );
            $urlElement->addChild('changefreq', 'weekly');
            $urlElement->addChild('priority', '0.6');
        }

        return $xml->asXML();
    }
}
