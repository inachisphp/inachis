<?php

namespace App\Tests\phpunit\Util;

use App\Entity\Page;
use App\Entity\Revision;
use App\Util\ContentRevisionCompare;
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