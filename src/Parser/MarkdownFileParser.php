<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Parser;

use Inachis\Entity\Category;
use Inachis\Entity\Page;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;

class MarkdownFileParser
{
    /**
     * Regular expression for matching an H1 from markdown
     */
    public const PARSE_TITLE = '/^# (.+)$/m';

    /**
     * Regular expression for matching an H2 from markdown
     */
    public const PARSE_SUBTITLE = '/^## (.+)$/m';

    /**
     * Regular expression for matching dates in YYYY-mm-dd format
     */
    public const PARSE_DATE = '/^(\d{4}-\d{2}-\d{2})( \d{2}:\d{2})?(:\d{2})?$/m';

    /**
     * Regular expression for matching a slash-separated category path
     */
    public const PARSE_CATEGORY_PATH = '/^[a-z0-9 &]+(\/[a-z0-9 &]+)*$/mi';

    /**
     * @var ObjectManager
     */
    private ObjectManager $em;

    /**
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Row 0 - title
     * Row 1 - subtitle / post date
     * Row 2 - postdate / category
     * Row 3 - Category / null
     * Row 4+ - Post content
     * @param string $markdown
     * @return Page
     * @throws Exception
     */
    public function parse(string $markdown): Page
    {
        $page = new Page();
        $markdown = preg_split('/\R/', trim($markdown));
        if (!$markdown || count($markdown) < 2) {
            throw new Exception('Invalid blog markdown format.');
        }

        $offset = 1;
        if (preg_match(self::PARSE_TITLE, $markdown[0], $match)) {
            $page->setTitle(trim($match[1]));
        } else {
            throw new Exception('Invalid blog markdown format - entry must start with a title.');
        }
        if (preg_match(self::PARSE_SUBTITLE, $markdown[1], $match)) {
            $page->setSubTitle(trim($match[1]));
            ++$offset;
        }
        if (preg_match(self::PARSE_DATE, $markdown[$offset], $match)) {
            $page->setPostDate(new DateTime($match[0]));
            ++$offset;
        }
        if (preg_match(self::PARSE_CATEGORY_PATH, $markdown[$offset], $match)) {
            $categoryPath = array_filter(explode('/', trim($markdown[$offset])));
            $category = $this->resolveCategoryPath($categoryPath);
            if ($category) {
                $page->addCategory($category);
            }
            ++$offset;
        }
        $content = implode("\n", array_slice($markdown, $offset));
        $page->setContent(trim($content));

        return $page;
    }

    /**
     * @param array $path
     * @return Category|null
     */
    private function resolveCategoryPath(array $path): ?Category
    {
        if (empty($path)) {
            return null;
        }

        $repo = $this->em->getRepository(Category::class);
        $parent = null;

        foreach ($path as $segment) {
            $title = str_replace('-', ' ', trim($segment));

            $criteria = ['title' => $title];
            if ($parent !== null) {
                $criteria['parent'] = $parent;
            }
            $category = $repo->findOneBy($criteria);

            if (!$category) {
                return $parent;
            }
            $parent = $category;
        }

        return $parent;
    }
}
