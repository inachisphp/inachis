<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use Inachis\Entity\Series;
use Inachis\Repository\CategoryRepository;
use Inachis\Repository\SeriesRepository;
use Inachis\Repository\TagRepository;
use Inachis\Repository\UrlRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates an XML sitemap for the site, including posts, categories, tags, and series.
 * The sitemap is split into multiple files if there are more than 50,000 URLs, and an index
 * file is created to reference them.
 */
class SitemapGenerator
{
    /** @var int Maximum number of URLs per sitemap file, as per sitemap protocol limits */
    private const MAX_URLS_PER_FILE = 50000;

    /**
     * SitemapGenerator constructor.
     *
     * @param UrlRepository $urlRepository
     * @param CategoryRepository $categoryRepository
     * @param TagRepository $tagRepository
     * @param SeriesRepository $seriesRepository
     * @param UrlGeneratorInterface $router
     * @param ParameterBagInterface $params
     */
    public function __construct(
        private readonly UrlRepository $urlRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
        private readonly SeriesRepository $seriesRepository,
        private readonly UrlGeneratorInterface $router,
        private readonly ParameterBagInterface $params
    ) {
    }

    /**
     * Generates the sitemap files and saves them to the public directory.
     */
    public function generate(): void
    {
        $publicDir = rtrim(
            $this->params->get('kernel.project_dir'),
            '/'
        ) . '/public';

        $sitemapDir = $publicDir . '/sitemaps';

        if (!is_dir($sitemapDir)) {
            mkdir($sitemapDir, 0755, true);
        }

        $files = [];

        $files = array_merge(
            $files,
            $this->generatePosts($sitemapDir)
        );

        $files = array_merge(
            $files,
            $this->generateSeries($sitemapDir)
        );

        $files = array_merge(
            $files,
            $this->generateCategories($sitemapDir)
        );

        $files = array_merge(
            $files,
            $this->generateTags($sitemapDir)
        );

        $this->generateIndex(
            $publicDir . '/sitemap.xml',
            $files
        );
    }

    /**
     * Generates sitemap files for posts, splitting into multiple files if necessary.
     *
     * @param string $dir
     * @return array<string>
     */
    private function generatePosts(string $dir): array
    {
        $files = [];
        $total = $this->urlRepository->countSitemapUrls();
        $fileNumber = 1;

        for (
            $offset = 0;
            $offset < $total;
            $offset += self::MAX_URLS_PER_FILE
        ) {
            $filename = "sitemap-posts-{$fileNumber}.xml";

            $writer = $this->createWriter(
                "{$dir}/{$filename}"
            );

            $urlBatch = $this->urlRepository->findSitemapUrlsBatch(
                $offset,
                self::MAX_URLS_PER_FILE
            );
            foreach ($urlBatch as $url) {
                /** @var \Inachis\Entity\Url $url */
                $content = $url->getContent();

                $writer->startElement('url');
                $writer->writeElement(
                    'loc',
                    $this->absoluteUrl('/' . ltrim($url->getLink(), '/'))
                );
                $writer->writeElement(
                    'lastmod',
                    $content->getModDate()->format('Y-m-d')
                );
                $writer->endElement();
            }

            $this->closeWriter($writer);

            $files[] = $filename;

            $fileNumber++;
        }

        return $files;
    }

    /**
     * Generate series sitemap files, splitting into multiple files if necessary.
     *
     * @param string $dir
     * @return array<string>
     */
    private function generateSeries(string $dir): array
    {
        return $this->generateEntitySitemapFiles(
            $dir,
            'series',
            $this->seriesRepository->countPublicSeries(),
            fn(int $offset, int $limit)
                => $this->seriesRepository->findPublicSeriesBatch(
                    $offset,
                    $limit
                ),
            function (Series $series): string {
                return sprintf(
                    '/%s-%s',
                    $series->getLastDate()?->format('Y'),
                    $series->getUrl()
                );
            },
            fn(Series $series)
                => $series->getModDate()
        );
    }

    /**
     * Generate category sitemap files, splitting into multiple files if necessary.
     *
     * @param string $dir
     * @return array<string>
     */
    private function generateCategories(string $dir): array
    {
        return $this->generateEntitySitemapFiles(
            $dir,
            'categories',
            $this->categoryRepository->countVisibleCategories(),
            fn($o, $l)
                => $this->categoryRepository->findBatch($o, $l),
            fn($c)
                => '/category/' . rawurlencode($c->getTitle()),
            fn() => null
        );
    }

    /**
     * Generate tag sitemap files, splitting into multiple files if necessary.
     *
     * @param string $dir
     * @return array<string>
     */
    private function generateTags(string $dir): array
    {
        return $this->generateEntitySitemapFiles(
            $dir,
            'tags',
            $this->tagRepository->countTags(),
            fn($o, $l)
                => $this->tagRepository->findBatch($o, $l),
            fn($t)
                => '/tag/' . rawurlencode($t->getTitle()),
            fn() => null
        );
    }

    /**
     * Helper method to generate sitemap files for a given entity type, handling pagination and file splitting.
     *
     * @param string $dir
     * @param string $prefix
     * @param integer $total
     * @param callable $loader
     * @param callable $urlBuilder
     * @param callable $lastMod
     * @return array<string>
     */
    private function generateEntitySitemapFiles(
        string $dir,
        string $prefix,
        int $total,
        callable $loader,
        callable $urlBuilder,
        callable $lastMod
    ): array {
        $files = [];
        $fileNumber = 1;

        for (
            $offset = 0;
            $offset < $total;
            $offset += self::MAX_URLS_PER_FILE
        ) {
            $filename = "sitemap-{$prefix}-{$fileNumber}.xml";

            $writer = $this->createWriter(
                "{$dir}/{$filename}"
            );

            foreach (
                $loader(
                    $offset,
                    self::MAX_URLS_PER_FILE
                ) as $item
            ) {
                $writer->startElement('url');

                $writer->writeElement(
                    'loc',
                    $this->absoluteUrl(
                        $urlBuilder($item)
                    )
                );

                $date = $lastMod($item);

                if ($date instanceof \DateTimeInterface) {
                    $writer->writeElement(
                        'lastmod',
                        $date->format('Y-m-d')
                    );
                }

                $writer->endElement();
            }

            $this->closeWriter($writer);

            $files[] = $filename;

            $fileNumber++;
        }

        return $files;
    }

    /**
     * Generate the sitemap index file that references all the individual sitemap files.
     *
     * @param string $filename
     * @param array<string> $files
     */
    private function generateIndex(
        string $filename,
        array $files
    ): void {
        $writer = new \XMLWriter();

        $writer->openURI($filename);
        $writer->startDocument(
            '1.0',
            'UTF-8'
        );

        $writer->startElement('sitemapindex');

        $writer->writeAttribute(
            'xmlns',
            'http://www.sitemaps.org/schemas/sitemap/0.9'
        );

        foreach ($files as $file) {
            $writer->startElement('sitemap');

            $writer->writeElement(
                'loc',
                $this->absoluteUrl('/sitemaps/' . $file)
            );
            $writer->writeElement(
                'lastmod',
                date('Y-m-d')
            );

            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    /**
     * Create an XML writer for generating sitemap files.
     *
     * @param string $filename
     * @return \XMLWriter
     */
    private function createWriter(
        string $filename
    ): \XMLWriter {
        $writer = new \XMLWriter();

        $writer->openURI($filename);

        $writer->startDocument(
            '1.0',
            'UTF-8'
        );

        $writer->startElement('urlset');

        $writer->writeAttribute(
            'xmlns',
            'http://www.sitemaps.org/schemas/sitemap/0.9'
        );

        return $writer;
    }

    /**
     * Close the XML writer and flush the output.
     *
     * @param \XMLWriter $writer
     */
    private function closeWriter(
        \XMLWriter $writer
    ): void {
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    /**
     * Convert a relative URL path to an absolute URL using the router's base URL.
     *
     * @param string $path
     * @return string
     */
    private function absoluteUrl(
        string $path
    ): string {
        $base = $this->router->generate(
            'inachis_default_homepage',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return rtrim($base, '/')
            . '/'
            . ltrim($path, '/');
    }
}
