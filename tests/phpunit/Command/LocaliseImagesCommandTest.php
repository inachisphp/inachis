<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Command;

use App\Command\LocaliseImagesCommand;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Repository\ImageRepository;
use App\Repository\PageRepository;
use App\Repository\PageRepositoryInterface;
use App\Repository\SeriesRepository;
use App\Service\Image\ContentImageUpdater;
use App\Service\Image\ImageExtractor;
use App\Service\Image\ImageLocaliser;
use ArrayIterator;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LocaliseImagesCommandTest extends TestCase
{
    private EntityManagerInterface $em;
    private ImageExtractor $extractor;
    private ImageLocaliser $localiser;
    private ContentImageUpdater $updater;

    private ImageRepository $imageRepo;
    private PageRepository $pageRepo;
    private SeriesRepository $seriesRepo;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->extractor = $this->createMock(ImageExtractor::class);
        $this->localiser = $this->createMock(ImageLocaliser::class);
        $this->updater = $this->createMock(ContentImageUpdater::class);
        $command = new LocaliseImagesCommand(
            $this->em,
            $this->extractor,
            $this->localiser,
            $this->updater
        );
        $this->commandTester = new CommandTester($command);

        $this->imageRepo = $this->getMockBuilder(ImageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $this->pageRepo = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $this->seriesRepo = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
    }

    public function testExecuteWithDryRun(): void
    {
        $iterableMock = $this->getMockBuilder(ArrayIterator::class)
            ->setConstructorArgs([[]])
            ->getMock();
        $paginatorMock = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginatorMock->method('getIterator')->willReturn($iterableMock);

        $imageEntity = $this->createMock(Image::class);
        $imageEntity->method('getFilename')->willReturn('https://example.com/img1.jpg');
        $this->imageRepo->method('getAll')->willReturn($paginatorMock);

        $pageEntity = $this->createMock(Page::class);
        $pageEntity->method('getContent')->willReturn('<img src="https://example.com/foo.jpg" />');
        $this->pageRepo->method('getAll')->willReturn($paginatorMock);

        $seriesEntity = $this->createMock(Series::class);
        $seriesEntity->method('getDescription')->willReturn('<img src="https://example.com/bar.jpg" />');
        $this->seriesRepo->method('getAll')->willReturn($paginatorMock);

        $this->em->method('getRepository')->willReturnMap([
            [Image::class,  $this->imageRepo],
            [Page::class,   $this->pageRepo],
            [Series::class, $this->seriesRepo],
        ]);

        $this->extractor->expects($this->exactly(2))
            ->method('extractFromContent')
            ->willReturn(['https://example.com/foo.jpg']);

        $this->localiser->expects($this->never())->method('downloadToLocal');
        $this->updater->expects($this->never())->method('updateEntity');

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processing image…', $output);
        $this->assertStringContainsString('Processing page…', $output);
        $this->assertStringContainsString('Processing series…', $output);
    }

    public function testExecuteWithoutDryRun(): void
    {
        $iterableMock = $this->getMockBuilder(ArrayIterator::class)
            ->setConstructorArgs([[]])
            ->getMock();
        $paginatorMock = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginatorMock->method('getIterator')->willReturn($iterableMock);
        $entity = $this->createMock(Page::class);
        $entity->method('getContent')->willReturn('<img src="https://example.com/image.jpg" />');
        $this->pageRepo->method('getAll')->willReturn($paginatorMock);
        $this->imageRepo->method('getAll')->willReturn($paginatorMock);
        $this->seriesRepo->method('getAll')->willReturn($paginatorMock);

        $this->em->method('getRepository')->willReturnMap([
            [Image::class,  $this->imageRepo],
            [Page::class,   $this->pageRepo],
            [Series::class, $this->seriesRepo],
        ]);

        $this->extractor->expects($this->once())
            ->method('extractFromContent')
            ->with('<img src="https://example.com/image.jpg" />')
            ->willReturn(['https://example.com/image.jpg']);

        $this->localiser->expects($this->once())
            ->method('downloadToLocal')
            ->with('https://example.com/image.jpg')
            ->willReturn('/var/www/public/imgs/image.jpg');

        $this->updater->expects($this->once())
            ->method('updateEntity')
            ->with(
                $entity,
                'content',
                [
                    'source'      => ['https://example.com/image.jpg'],
                    'destination' => ['/var/www/public/imgs/image.jpg'],
                ],
                true
            );

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('done.', $this->commandTester->getDisplay());
    }
}
