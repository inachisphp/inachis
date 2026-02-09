<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Category;

use Inachis\Model\Category\CategoryExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;

/**
 * XML writer for categories.
 */
final class CategoryXmlWriter extends AbstractXmlExportWriter
{
    /**
     * Checks if the writer supports the given format.
     *
     * @param string $format The format to check.
     * @return bool True if the writer supports the format, false otherwise.
     */
    public function supports(string $format): bool
    {
        return $format === 'xml';
    }

    /**
     * Checks if the writer supports the given content domain.
     *
     * @param string|null $domain The domain to check.
     * @return bool True if the writer supports the domain, false otherwise.
     */
    public function supportsDomain(?string $domain): bool
    {
        return $domain === 'category';
    }

    /**
     * The root node for the XML document.
     *
     * @return string The root node name.
     */
    protected function rootNodeName(): string
    {
        return 'categories';
    }

    /**
     * The item node for the XML document.
     *
     * @return string The item node name.
     */
    protected function itemNodeName(): string
    {
        return 'category';
    }

    /**
     * Writes a single category DTO to XML.
     *
     * @param \SimpleXMLElement $xml The XML element to write to.
     * @param CategoryExportDto $item The category DTO to write.
     */
    protected function writeItem(\SimpleXMLElement $xml, object $item): void
    {
        /** @var CategoryExportDto $item */
        $this->optional($xml, 'id', $item->id);
        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'description', $item->description);
        $this->optional($xml, 'image', $item->image);
        $this->optional($xml, 'icon', $item->icon);
        $this->boolean($xml, 'visible', $item->visible, 'true', 'false');
        $this->optional($xml, 'parentId', $item->parentId);

        if (!empty($item->childrenIds)) {
            $childrenXml = $xml->addChild('children');
            foreach ($item->childrenIds as $childId) {
                $childrenXml->addChild('child', $childId);
            }
        }

        $this->optional($xml, 'fullPath', $item->fullPath);
    }
}
