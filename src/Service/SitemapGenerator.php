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
    private RouterInterface $router;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;
    private SeriesRepository $seriesRepository;
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
        $defaults = $this->urlRepository->findBy(['default' => true]);
        foreach ($defaults as $urlEntity) {
            if (!method_exists($urlEntity, 'getPath')) {
                continue; // guard
            }
            $path = $urlEntity->getPath();
            // ensure leading slash
            if ($path[0] !== '/') {
                $path = '/' . $path;
            }
            // apply same exclusion rule
            if (preg_match('#^/incc#', $path)) {
                continue;
            }
            $url = $baseUrl . $path;
            $urlElement = $xml->addChild('url');
            $urlElement->addChild('loc', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
            $urlElement->addChild('lastmod', (new DateTime())->format('Y-m-d'));
            $urlElement->addChild('changefreq', 'weekly');
            $urlElement->addChild('priority', '0.6');
        }

        return $xml->asXML();
    }
}
