<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Page;

use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Export\AbstractXmlExportWriter;

/**
 * XML writer for pages.
 */
final class PageXmlWriter extends AbstractXmlExportWriter
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
        return 'xml' === $format;
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
     * The root node for the XML document.
     */
    protected function rootNodeName(): string
    {
        return 'pages';
    }

    /**
     * The item node for the XML document.
     */
    protected function itemNodeName(): string
    {
        return 'page';
    }

    /**
     * Writes the given page to XML format.
     */
    protected function writeItem(\SimpleXMLElement $xml, object $item): void
    {
        if (!$item instanceof PageExportDto) {
            throw new \InvalidArgumentException('Expected instance of '.PageExportDto::class);
        }

        $this->optional($xml, 'title', $item->title);
        $this->optional($xml, 'subTitle', $item->subTitle);
        $this->optional($xml, 'content', $item->content);
        $xml->addChild('type', $item->type);
        $xml->addChild('status', $item->status);
        $this->boolean($xml, 'visible', $item->visible, 'public', 'private');
        $this->boolean($xml, 'allowComments', $item->allowComments);

        $categories = $xml->addChild('categories');
        if (null !== $categories) {
            foreach ($item->categories as $category) {
                $categories->addChild('category', $category->path);
            }
        }

        $tags = $xml->addChild('tags');
        if (null !== $tags) {
            foreach ($item->tags as $tag) {
                $tags->addChild('tag', $tag->title);
            }
        }
    }
}
