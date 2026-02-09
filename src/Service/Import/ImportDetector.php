<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import;

/**
 * Detects the type of import based on the JSON content
 */
class ImportDetector
{
	/**
	 * Detects the type of import based on the JSON content
	 *
     * @param array $data The decoded JSON/XML data
     * @return string One of: category, post, series
     */
    public function detectImportType(array $data): string
    {
        $firstItem = $data[0] ?? null;

        if (!$firstItem || !is_array($firstItem)) {
            throw new \InvalidArgumentException("Import data is empty or invalid.");
        }

        return match (true) {
            // Category: Unique keys 'parentId', 'childrenIds', or 'fullPath'
            array_key_exists('parentId', $firstItem) || array_key_exists('fullPath', $firstItem)
                => 'category',

            // Post: Unique key 'content' (contains the long-form markdown/HTML)
            array_key_exists('content', $firstItem)
                => 'post',

            // Series: Unique keys 'items' (the list of post titles) or 'firstDate'
            array_key_exists('items', $firstItem) && array_key_exists('url', $firstItem)
                => 'series',

            default => throw new \InvalidArgumentException("Structure does not match Category, Post, or Series."),
        };
    }
}