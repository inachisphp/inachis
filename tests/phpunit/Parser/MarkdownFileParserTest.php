<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Parser;

use App\Entity\Category;
use App\Entity\Page;
use App\Parser\MarkdownFileParser;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class MarkdownFileParserTest extends TestCase
{
    private ObjectManager $em;
    private ObjectRepository $repo;
    private MarkdownFileParser $parser;

    public function setUp(): void
    {
        $this->em = $this->createMock(ObjectManager::class);
        $this->repo = $this->createMock(ObjectRepository::class);
        $this->em->method('getRepository')->with(Category::class)->willReturn($this->repo);
        $this->parser  = new MarkdownFileParser($this->em);

        parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testParsesMarkdownWithTitleSubtitleDateCategoryAndContent(): void
    {
        $markdown = <<<MD
# A title
## Sub-title
2025-01-01 01:23:45
Trips/Europe/Wales


This is a test
MD;
        $category1 = (new Category())->setTitle('Trips');
        $category2 = (new Category())->setTitle('Europe')->setParent($category1);
        $category3 = (new Category())->setTitle('Wales')->setParent($category2);

        $this->repo
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($category1, $category2, $category3) {
                return match ($criteria['title']) {
                    'Trips' => $category1,
                    'Europe' => ($criteria['parent'] === $category1) ? $category2: null,
                    'Wales' => ($criteria['parent'] === $category2) ? $category3 : null,
                    default => null,
                };
            });

        $page = $this->parser->parse($markdown);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('A title', $page->getTitle());
        $this->assertSame('Sub-title', $page->getSubTitle());
        $this->assertInstanceOf(DateTime::class, $page->getPostDate());
        $this->assertEquals('2025-01-01', $page->getPostDate()->format('Y-m-d'));
        $this->assertSame('This is a test', $page->getContent());
        $this->assertTrue($page->getCategories()->contains($category3));
    }

    /**
     * @throws Exception
     */
    public function testParsesWithoutSubtitle(): void
    {
        $markdown = <<<MD
# Title Only
2025-01-01
Trips
Post body.
MD;
        $category = (new Category())->setTitle('Trips');
        $this->repo->method('findOneBy')->willReturn($category);

        $page = $this->parser->parse($markdown);

        $this->assertSame('Title Only', $page->getTitle());
        $this->assertNull($page->getSubTitle());
        $this->assertSame('Post body.', $page->getContent());
        $this->assertTrue($page->getCategories()->contains($category));
    }

    /**
     * @throws Exception
     */
    public function testParsesWithoutDate(): void
    {
        $markdown = <<<MD
# Title
## Subtitle
tech
Post content.
MD;
        $category = (new Category())->setTitle('tech');
        $this->repo->method('findOneBy')->willReturn($category);

        $page = $this->parser->parse($markdown);

        $this->assertSame('Subtitle', $page->getSubTitle());
    }

    /**
     * @throws Exception
     */
    public function testHandlesMissingCategoryGracefully(): void
    {
        $markdown = <<<MD
# Title
## Subtitle
2025-10-17
nonexistent
Post content.
MD;
        $this->repo->method('findOneBy')->willReturn(null);

        $page = $this->parser->parse($markdown);

        $this->assertCount(0, $page->getCategories());
        $this->assertSame('Post content.', $page->getContent());
    }

    public function testHandlesInvalidMarkdown(): void
    {
        $this->expectException(Exception::class);
        $this->parser->parse("Invalid line");
    }

    /**
     * @throws Exception
     */
    public function testPartialCategoryPathStopsAtValidParent(): void
    {
        $markdown = <<<MD
# Post Title
## Subtitle
2025-10-17
Trips/Europe/Scotland
Content here
MD;
        $category1 = (new Category())->setTitle('Trips');
        $category2 = (new Category())->setTitle('Europe')->setParent($category1);

        // "frameworks" missing — should return deepest valid parent (php)
        $this->repo->method('findOneBy')->willReturnCallback(function ($criteria) use ($category1, $category2) {
            return match ($criteria['title']) {
                'Trips' => $category1,
                'Europe' => ($criteria['parent'] === $category1) ? $category2 : null,
                default => null,
            };
        });

        $page = $this->parser->parse($markdown);

        $this->assertTrue($page->getCategories()->contains($category2));
    }

    public function testParssMarkdownWithoutTitleThrowsException(): void
    {
        $this->expectException(Exception::class);
        $markdown = <<<MD
This is some content
split over two lines without a title.
MD;

        $this->parser->parse($markdown);
    }

    /**
     * @throws Exception
     */
    public function testParsesWithNoCategories(): void
    {
        $markdown = <<<MD
# Post Title
## Subtitle
2025-10-17
a
Content here
MD;
        $page = $this->parser->parse($markdown);

        $this->assertEmpty($page->getCategories());
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveCategoryPathReturnsNullForEmptyPath(): void
    {
        $reflection = new ReflectionClass($this->parser);
        $method = $reflection->getMethod('resolveCategoryPath');
        $method->setAccessible(true);

        $result = $method->invoke($this->parser, []);

        $this->assertNull($result);
    }
}
