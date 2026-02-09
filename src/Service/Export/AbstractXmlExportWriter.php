<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export;

use SimpleXMLElement;

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
     */
    abstract protected function writeItem(SimpleXMLElement $xml, object $item): void;

    /**
     * XML write entry point
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

        return $xml->asXML();
    }

    /**
     * Helper for optional text nodes
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
