<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content;

use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Service\Content\ContentRevisionCompare;
use PHPUnit\Framework\TestCase;

class ContentRevisionCompareTest extends TestCase
{
    protected ContentRevisionCompare $revisionCompare;

    public function setUp(): void
    {
        $this->revisionCompare  = new ContentRevisionCompare();
        parent::setUp();
    }

    public function testDoesPageMatchRevision(): void
    {
        $page = new Page('Page 1', 'some content');
        $revision = new Revision();
        $revision->setTitle('Page 1')->setContent('some content');
        $this->assertTrue($this->revisionCompare->doesPageMatchRevision($page, $revision));
        $revision->setTitle('Page 2')->setContent('some content');
        $this->assertFalse($this->revisionCompare->doesPageMatchRevision($page, $revision));
        $revision->setTitle('Page 1')->setContent('some other content');
        $this->assertFalse($this->revisionCompare->doesPageMatchRevision($page, $revision));
        $revision->setTitle('Page 2')->setContent('some other content');
        $this->assertFalse($this->revisionCompare->doesPageMatchRevision($page, $revision));
    }
}
