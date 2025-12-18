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
use App\Repository\SeriesRepository;
use App\Service\Image\ContentImageUpdater;
use App\Service\Image\ImageExtractor;
use App\Service\Image\ImageLocaliser;
use ArrayIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LocaliseImagesCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ImageExtractor $extractor;
    private ImageLocaliser $localiser;
    private ContentImageUpdater $updater;

    private ImageRepository $imageRepository;
    private PageRepository $pageRepository;
    private SeriesRepository $seriesRepository;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->extractor = $this->createMock(ImageExtractor::class);
        $this->localiser = $this->createMock(ImageLocaliser::class);
        $this->updater = $this->createMock(ContentImageUpdater::class);

        $command = new LocaliseImagesCommand(
            $this->entityManager,
            $this->extractor,
            $this->localiser,
            $this->updater
        );

        $this->commandTester = new CommandTester($command);

        $this->imageRepository = $this->getMockBuilder(ImageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $this->pageRepository = $this->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();
        $this->seriesRepository = $this->getMockBuilder(SeriesRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAll'])
            ->getMock();

        $this->entityManager->expects($this->atLeast(1))->method('getRepository')->willReturnMap([
            [Image::class,  $this->imageRepository],
            [Page::class,   $this->pageRepository],
            [Series::class, $this->seriesRepository],
        ]);
    }

    /** Helper to create a mock paginator */
    private function createPaginatorMock(array $entities): Paginator
    {
        $paginator = $this->getMockBuilder(Paginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $paginator->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($entities));

        return $paginator;
    }

    /** Shared setup for entities and paginators */
    private function prepareEntities(): array
    {
        $page = $this->createMock(Page::class);
        $page->expects($this->once())
            ->method('getContent')
            ->willReturn('<img src="https://example.com/page.jpg" />');

        $series = $this->createMock(Series::class);
        $series->expects($this->once())
            ->method('getDescription')
            ->willReturn('<img src="https://example.com/series.jpg" />');

        $image = $this->createMock(Image::class);
        $image->expects($this->once())
            ->method('getFilename')
            ->willReturn('https://example.com/image.jpg');

        $this->pageRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($this->createPaginatorMock([$page]));
        $this->seriesRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($this->createPaginatorMock([$series]));
        $this->imageRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($this->createPaginatorMock([$image]));

        return [$page, $series, $image];
    }

    public function testExecuteWithDryRun(): void
    {
        [$page, $series] = $this->prepareEntities();

        // Extractor is only called for Page and Series content
        $this->extractor->expects($this->exactly(2))
            ->method('extractFromContent')
            ->willReturnCallback(fn($content) => [$content]);

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
        [$page, $series, $image] = $this->prepareEntities();

        // Extractor only called for Page and Series
        $this->extractor->expects($this->exactly(2))
            ->method('extractFromContent')
            ->willReturnCallback(fn($content) => [$content]);

        $this->localiser->expects($this->exactly(3))
            ->method('downloadToLocal')
            ->willReturnCallback(fn($url) => str_replace('https://example.com/', '/var/www/public/imgs/', $url));

        $this->updater->expects($this->exactly(3))
            ->method('updateEntity');

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processing image…', $output);
        $this->assertStringContainsString('Processing page…', $output);
        $this->assertStringContainsString('Processing series…', $output);
        $this->assertStringContainsString('done.', $output);
    }
}
