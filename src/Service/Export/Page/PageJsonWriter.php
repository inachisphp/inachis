<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Page;

use Inachis\Service\Export\ExportWriterInterface;

/**
 * JSON writer for pages.
 */
class PageJsonWriter implements ExportWriterInterface
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
        return 'json' === $format;
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
     * Writes the given pages to JSON format.
     *
     * @param iterable $pages the pages to write
     *
     * @return string the exported pages
     */
    public function write(iterable $pages): string
    {
        return json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
