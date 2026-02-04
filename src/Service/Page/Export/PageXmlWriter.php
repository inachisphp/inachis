<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Export;

use Inachis\Model\Page\PageExportDto;
use Inachis\Service\Page\Export\PageExportWriterInterface;
use SimpleXMLElement;

/**
 * XML writer for pages.
 */
class PageXmlWriter implements PageExportWriterInterface
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
     * Writes the given pages to XML format.
     *
     * @param iterable $pages The pages to write.
     * @return string The exported pages.
     */
    public function write(iterable $pages): string
    {
        $xml = new SimpleXMLElement('<pages/>');

        foreach ($pages as $page) {
            $pageXml = $xml->addChild('page');
            $pageXml->addChild('title', htmlspecialchars($page->title));
            if ($page->subTitle) {
                $pageXml->addChild('subTitle', htmlspecialchars($page->subTitle));
            }
            $pageXml->addChild('content', htmlspecialchars($page->content ?? ''));
            $pageXml->addChild('type', $page->type);
            $pageXml->addChild('status', $page->status);
            $pageXml->addChild('visibility', $page->visibility ? 'public' : 'private');
            $pageXml->addChild('allowComments', $page->allowComments ? 'true' : 'false');

            if ($page->language) $pageXml->addChild('language', $page->language);
            if ($page->timezone) $pageXml->addChild('timezone', $page->timezone);
            if ($page->postDate) $pageXml->addChild('postDate', $page->postDate);

            $categoriesXml = $pageXml->addChild('categories');
            foreach ($page->categories as $category) {
                $categoriesXml->addChild('category', htmlspecialchars($category->path));
            }
            $tagsXml = $pageXml->addChild('tags');
            foreach ($page->tags as $tag) {
                $tagsXml->addChild('tag', htmlspecialchars($tag->title));
            }
        }

        return $xml->asXML();
    }
}
