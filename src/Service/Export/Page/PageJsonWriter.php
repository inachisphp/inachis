<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
     * @param string $format The format to check.
     * @return bool True if the writer supports the format, false otherwise.
     */
    public function supports(string $format): bool
    {
        return $format === 'json';
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
     * Writes the given pages to JSON format.
     *
     * @param iterable $pages The pages to write.
     * @return string The exported pages.
     */
    public function write(iterable $pages): string
    {
        return json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}