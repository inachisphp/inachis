<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Export;

use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;
use SimpleXMLElement;

/**
 * XML writer for pages.
 */
final class PageXmlWriter extends AbstractXmlExportWriter
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
     * @param string|null $domain The content domain to check.
     * @return bool True if the writer supports the domain, false otherwise.
     */
    public function supportsDomain(?string $domain): bool
    {
        return true;
    }

    /**
     * The root node for the XML document.
     *
     * @return string
     */
    protected function rootNodeName(): string
    {
        return 'pages';
    }

    /**
     * The item node for the XML document.
     *
     * @return string
     */
    protected function itemNodeName(): string
    {
        return 'page';
    }

    /**
     * Writes the given pages to XML format.
     *
     * @param iterable $pages The pages to write.
     * @return string The exported pages.
     */
    protected function writeItem(SimpleXMLElement $xml, object $item): void
    {
        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'subTitle', $item->subTitle);
        $this->optional($xml, 'content', $item->content);
        $xml->addChild('type', $item->type);
        $xml->addChild('status', $item->status);
        $this->boolean($xml, 'visibility', $item->visibility, 'public', 'private');
        $this->boolean($xml, 'allowComments', $item->allowComments);

        $categories = $xml->addChild('categories');
        foreach ($item->categories as $category) {
            $categories->addChild('category', $category->path);
        }

        $tags = $xml->addChild('tags');
        foreach ($item->tags as $tag) {
            $tags->addChild('tag', $tag->title);
        }
    }
}
