<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Export\Series;

use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;

/**
 * XML writer for series.
 */
final class SeriesXmlWriter extends AbstractXmlExportWriter
{
    /**
     * Checks if the writer supports the given format.
     */
    public function supports(string $format): bool
    {
        return 'xml' === $format;
    }

    /**
     * Checks if the writer supports the given content domain.
     */
    public function supportsDomain(?string $domain): bool
    {
        return 'series' === $domain;
    }

    /**
     * The root node for the XML document.
     */
    protected function rootNodeName(): string
    {
        return 'seriesCollection';
    }

    /**
     * The item node for the XML document.
     */
    protected function itemNodeName(): string
    {
        return 'series';
    }

    /**
     * Writes the given series to XML format.
     *
     * @param SeriesExportDto $item
     */
    protected function writeItem(\SimpleXMLElement $xml, object $item): void
    {
        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'subTitle', $item->subTitle);
        $this->optional($xml, 'description', $item->description);
        $xml->addChild('url', $item->url);
        $this->optional($xml, 'firstDate', $item->firstDate);
        $this->optional($xml, 'lastDate', $item->lastDate);
        $this->boolean($xml, 'visible', $item->visible, 'public', 'private');

        $itemsNode = $xml->addChild('items');
        foreach ($item->items as $pageItem) {
            if ($pageItem instanceof PageExportDto) {
                $pageNode = $itemsNode->addChild('page');
                $this->optional($pageNode, 'title', $pageItem->title);
                $this->optional($pageNode, 'subTitle', $pageItem->subTitle);
                $this->optional($pageNode, 'content', $pageItem->content);
                $this->optional($pageNode, 'type', $pageItem->type);
                $this->optional($pageNode, 'status', $pageItem->status);
                $this->boolean($pageNode, 'visible', $pageItem->visible, 'public', 'private');
                $this->optional($pageNode, 'postDate', $pageItem->postDate);

                if (!empty($pageItem->categories)) {
                    $catsNode = $pageNode->addChild('categories');
                    foreach ($pageItem->categories as $cat) {
                        $catsNode->addChild('category', $cat->path);
                    }
                }
                if (!empty($pageItem->tags)) {
                    $tagsNode = $pageNode->addChild('tags');
                    foreach ($pageItem->tags as $tag) {
                        $tagsNode->addChild('tag', $tag->title);
                    }
                }
                if (!empty($pageItem->urls)) {
                    $urlsNode = $pageNode->addChild('urls');
                    foreach ($pageItem->urls as $url) {
                        $uNode = $urlsNode->addChild('url', $url->path);
                        if ($url->default) {
                            $uNode->addAttribute('default', 'true');
                        }
                    }
                }
            } else {
                $itemsNode->addChild('item', (string) $pageItem);
            }
        }
    }
}
