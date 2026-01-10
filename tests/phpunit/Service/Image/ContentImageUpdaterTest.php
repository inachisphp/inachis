<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Service\Image;

use Inachis\Entity\Page;
use Inachis\Entity\Revision;
use Inachis\Repository\RevisionRepository;
use Inachis\Service\Image\ContentImageUpdater;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DummyEntity
{
    private string $content = 'Original content with https://example.com/image.jpg';
    private DateTime $modDate;

    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $value): void
    {
        $this->content = $value;
    }
    public function setModDate(DateTime $date): void
    {
        $this->modDate = $date;
    }
}

class ContentImageUpdaterTest extends TestCase
{
    private EntityManagerInterface $em;
    private ContentImageUpdater $updater;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->updater = new ContentImageUpdater($this->em);
    }

    public function testUpdatesEntityContentAndPersists(): void
    {
        $page = new Page('test', 'https://example.com/image.jpg');
        $revision = $this->createMock(Revision::class);
        $revision->expects($this->once())
            ->method('setAction')
            ->with(RevisionRepository::UPDATED);
        $revisionRepository = $this->createMock(RevisionRepository::class);
        $revisionRepository->expects($this->once())
            ->method('hydrateNewRevisionFromPage')
            ->with($page)
            ->willReturn($revision);
        $this->em->method('getRepository')
            ->with(Revision::class)
            ->willReturn($revisionRepository);

        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($arg) use ($revision, $page) {
                static $call = 0;
                $call++;
                if ($call === 1) {
                    $this->assertSame($revision, $arg, 'Added Revision');
                } elseif ($call === 2) {
                    $this->assertSame($page, $arg, 'Updated entity');
                }
            });
        $this->em->expects($this->once())->method('flush');

        $this->updater->updateEntity(
            $page,
            'content',
            [
                'source' => ['https://example.com/image.jpg'],
                'destination' => ['/imgs/image.jpg']
            ],
            true
        );

        $this->assertStringContainsString('/imgs/image.jpg', $page->getContent());
    }
}
