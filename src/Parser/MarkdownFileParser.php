<?php

namespace App\Parser;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\Common\Persistence\ObjectManager;

class MarkdownFileParser
{
    /**
     *
     */
    const PARSE_TITLE = '/# (.*)/';

    /**
     *
     */
    const PARSE_SUBTITLE = '/## (.*)/';

    /**
     *
     */
    const PARSE_DATE = '/([0-9]{4})-([0-9]{2})-([0-9]{2})/';

    /**
     * @var
     */
    private $entityManager;

    /**
     * Row 0 - title
     * Row 1 - subtitle / post date
     * Row 2 - postdate / category
     * Row 3 - Category / null
     * Row 4+ - Post content
     * @param ObjectManager $entityManager
     * @param string $post
     * @return Page
     * @throws \Exception
     */
    public function parse(ObjectManager $entityManager, string $post): Page
    {
        $this->entityManager = $entityManager;
        $page = new Page();
        $post = preg_split('/[\r\n]/', $post, 5);
        $subTitleOffset = 0;
        if (preg_match(MarkdownFileParser::PARSE_TITLE, $post[0], $match)) {
            $page->setTitle(trim($match[1]));
        }
        if (preg_match(MarkdownFileParser::PARSE_SUBTITLE, $post[1], $match)) {
            $page->setSubTitle(trim($match[1]));
            $subTitleOffset = 1;
        }
        if (preg_match(MarkdownFileParser::PARSE_DATE, $post[1 + $subTitleOffset], $match)) {
            $page->setPostDate(new \DateTime($match[0]));
        }
        $category = $this->getCategoryFromPath(explode('/', $post[2 + $subTitleOffset]));
        $category = $entityManager->getRepository(Category::class)->findOneByTitle($category);
        if ($category) {
            $page->addCategory($category);
        }
        $page->setContent(trim($post[4]));

        return $page;
    }

    /**
     * @param array $categoryPath
     * @param Category|null $category
     * @return Category
     */
    private function getCategoryFromPath(array $categoryPath, Category $category = null): Category
    {
        if ($category === null) {
            $category = $categoryPath;
            if (is_array($categoryPath)) {
                $category = str_replace(['-'], [' '], array_shift($categoryPath));
            }
            $category = $this->entityManager->getRepository(Category::class)->findOneByTitle($category);
        }

        if (!empty($categoryPath) && !empty($category->getChildren())) {
            foreach ($category->getChildren() as $childCategory) {
                if ($childCategory->getTitle() === $categoryPath[0]) {
                    $category = $childCategory;
                    array_shift($categoryPath);

                    return $this->getCategoryFromPath($categoryPath, $category);
                }
            }
        }

        return $category;
    }
}
