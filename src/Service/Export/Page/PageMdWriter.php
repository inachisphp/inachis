<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Page;

use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Export\ExportWriterInterface;
use \SimpleXMLElement;

/**
 * XML writer for pages.
 */
class PageMdWriter implements ExportWriterInterface
{
    /**
     * Checks if the writer supports the given format.
     *
     * @param string $format The format to check.
     * @return bool True if the writer supports the format, false otherwise.
     */
    public function supports(string $format): bool
    {
        return $format === 'md';
    }

    /**
     * Checks if the writer supports the given content domain.
     *
     * @param string|null $domain The content domain to check.
     * @return bool True if the writer supports the domain, false otherwise.
     */
    public function supportsDomain(?string $domain): bool
    {
        return true;
    }

    /**
     * Writes the given pages to MD format.
     *
     * @param iterable $pages The pages to write.
     * @return string The exported pages.
     */
    public function write(iterable $pages, array $options = []): string
    {
        $output = '';
        foreach ($pages as $page) {
            if (!$page instanceof PageExportDto) {
                throw new \InvalidArgumentException('All items must be PageExportDto');
            }

            $title = $page->title;
            $subTitle = $page->subTitle ?? '';
            $date = $page->postDate ?? date('Y-m-d');
            $category = $page->categories[0]->path ?? '';
            $tags = array_map(fn($t) => $t->title, $page->tags);

            // YAML front matter
            $output .= "---\n";
            $output .= 'title: ' . json_encode($title) . "\n";
            if ($subTitle) {
                $output .= 'subTitle: ' . json_encode($subTitle) . "\n";
            }
            $output .= "date: {$date}\n";
            $output .= 'tags: ' . json_encode($tags) . "\n";
            $output .= 'category: ' . json_encode($category) . "\n";
            $output .= "---\n\n";

            // Markdown content
            $output .= "# {$title}\n";
            if ($subTitle) {
                $output .= "## {$subTitle}\n\n";
            }
            $output .= ($page->content ?? '') . "\n\n";
        }

        return $output;
    }
}
