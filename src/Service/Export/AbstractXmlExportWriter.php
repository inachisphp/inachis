<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export;

use SimpleXMLElement;

/**
 * Abstract base class for XML export writers.
 */
abstract class AbstractXmlExportWriter implements ExportWriterInterface
{
    /**
     * Root element name (e.g. "pages", "seriesCollection")
     */
    abstract protected function rootNodeName(): string;

    /**
     * Child element name (e.g. "page", "series")
     */
    abstract protected function itemNodeName(): string;

    /**
     * Write one DTO into XML
     *
     * @param SimpleXMLElement $xml The XML element to write to.
     * @param object $item The item to write.
     */
    abstract protected function writeItem(SimpleXMLElement $xml, object $item): void;

    /**
     * XML write entry point
     *
     * @param iterable<object> $items The items to write.
     * @return string The XML.
     */
    final public function write(iterable $items): string
    {
        $xml = new SimpleXMLElement(
            sprintf(
                '<?xml version="1.0" encoding="UTF-8"?><%s/>',
                $this->rootNodeName()
            )
        );

        foreach ($items as $item) {
            $itemXml = $xml->addChild($this->itemNodeName());
            $this->writeItem($itemXml, $item);
        }

        $xml = $xml->asXML();
        if ($xml === false) {
            throw new \RuntimeException('Failed to write XML');
        }

        return $xml;
    }

    /**
     * Helper for optional text nodes
     *
     * @param SimpleXMLElement $xml The XML element to write to.
     * @param string $name The name of the node.
     * @param string|null $value The value of the node.
     */
    protected function optional(
        SimpleXMLElement $xml,
        string $name,
        ?string $value
    ): void {
        if ($value !== null && $value !== '') {
            $xml->addChild($name, $value);
        }
    }

    /**
     * Helper for boolean nodes
     *
     * @param SimpleXMLElement $xml The XML element to write to.
     * @param string $name The name of the node.
     * @param bool $value The value of the node.
     * @param string $true The value to use for true.
     * @param string $false The value to use for false.
     */
    protected function boolean(
        SimpleXMLElement $xml,
        string $name,
        bool $value,
        string $true = 'true',
        string $false = 'false'
    ): void {
        $xml->addChild($name, $value ? $true : $false);
    }
}
