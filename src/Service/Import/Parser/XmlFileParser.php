<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Parser;

/**
 * File parser for XML files.
 */
class XmlFileParser implements FileParserInterface
{
    public function supports(string $extension): bool
    {
        return 'xml' === strtolower(ltrim($extension, '.'));
    }

    public function parse(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $filePath));
        }

        $content = file_get_contents($filePath);
        if (false === $content || '' === trim($content)) {
            throw new \InvalidArgumentException('Import file is empty.');
        }

        $useErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if (false === $xml) {
            $errorMessage = !empty($errors) ? $errors[0]->message : 'Invalid XML syntax.';
            throw new \InvalidArgumentException('Failed to parse XML file: ' . trim($errorMessage));
        }

        $items = [];
        foreach ($xml->children() as $child) {
            $items[] = $this->normalizeNode($this->simpleXmlToArray($child));
        }

        if (empty($items)) {
            $items[] = $this->normalizeNode($this->simpleXmlToArray($xml));
        }

        return $items;
    }

    private function simpleXmlToArray(\SimpleXMLElement $xml): array
    {
        $array = json_decode(json_encode($xml), true);
        return is_array($array) ? $array : [];
    }

    /**
     * Normalize nested container nodes (categories, tags, urls, items).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeNode(array $data): array
    {
        // Normalize categories container <categories><category>...</category></categories>
        if (isset($data['categories']) && is_array($data['categories'])) {
            $cats = $data['categories']['category'] ?? $data['categories'];
            $data['categories'] = is_string($cats) ? [$cats] : (array_is_list((array) $cats) ? (array) $cats : [(array) $cats]);
        }

        // Normalize tags container <tags><tag>...</tag></tags>
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = $data['tags']['tag'] ?? $data['tags'];
            $data['tags'] = is_string($tags) ? [$tags] : (array_is_list((array) $tags) ? (array) $tags : [(array) $tags]);
        }

        // Normalize urls container <urls><url>...</url></urls>
        if (isset($data['urls']) && is_array($data['urls'])) {
            $urls = $data['urls']['url'] ?? $data['urls'];
            $data['urls'] = is_string($urls) ? [$urls] : (array_is_list((array) $urls) ? (array) $urls : [(array) $urls]);
        }

        // Normalize items container <items><item>...</item></items>
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items']['item'] ?? $data['items'];
            $data['items'] = is_string($items) ? [$items] : (array_is_list((array) $items) ? (array) $items : [(array) $items]);
        }

        return $data;
    }
}
