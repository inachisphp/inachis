<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Page;

use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Export\ExportWriterInterface;

/**
 * Markdown writer for pages.
 */
class PageMdWriter implements ExportWriterInterface
{
    /**
     * Checks if the writer supports the given format.
     *
     * @param string $format the format to check
     *
     * @return bool true if the writer supports the format, false otherwise
     */
    public function supports(string $format): bool
    {
        return 'md' === $format;
    }

    /**
     * Checks if the writer supports the given content domain.
     *
     * @param string|null $domain the content domain to check
     *
     * @return bool true if the writer supports the domain, false otherwise
     */
    public function supportsDomain(?string $domain): bool
    {
        return true;
    }

    /**
     * Writes the given pages to MD format.
     *
     * @param iterable<object>     $pages   the pages to write
     * @param array<string, mixed> $options optional configuration options
     *
     * @return string the exported pages
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
            $tags = array_map(static fn (object $t): string => $t->title ?? '', $page->tags);

            // YAML front matter
            $output .= "---\n";
            $output .= 'title: '.json_encode($title)."\n";
            if ($subTitle) {
                $output .= 'subTitle: '.json_encode($subTitle)."\n";
            }
            $output .= "date: {$date}\n";
            $output .= 'tags: '.json_encode($tags)."\n";
            $output .= 'category: '.json_encode($category)."\n";
            $output .= "---\n\n";

            // Markdown content
            $output .= "# {$title}\n";
            if ($subTitle) {
                $output .= "## {$subTitle}\n\n";
            }
            $output .= ($page->content ?? '')."\n\n";
        }

        return $output;
    }
}
