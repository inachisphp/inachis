<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export;

/**
 * Interface for export writers.
 */
interface ExportWriterInterface
{
    /**
     * Checks if the writer supports the given format.
     *
     * @param string $format The format to check.
     * @return bool True if the writer supports the format, false otherwise.
     */
    public function supports(string $format): bool;

    /**
     * Checks if the writer supports the given content domain.
     *
     * @param string|null $domain The content domain to check.
     * @return bool True if the writer supports the domain, false otherwise.
     */
    public function supportsDomain(?string $domain): bool;

    /**
     * Writes the given content to the specified format.
     *
     * @param iterable $items The content to write.
     * @return string The exported content.
     */
    public function write(iterable $items): string;
}
