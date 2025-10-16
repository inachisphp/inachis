<?php

namespace App\Tests\phpunit\Command;

use App\Command\LocaliseImagesCommand;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Service\Image\ImageExtractor;
use App\Service\Image\ImageLocaliser;
use App\Service\Image\ContentImageUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

class DummyRepo
{
    public function getAll(int $a, int $b, array $c): array
    {
        return [];
    }
}

class LocaliseImagesCommandTest extends TestCase
{
    private $em;
    private $extractor;
    private $localiser;
    private $updater;
    private $command;
    private $commandTester;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->extractor = $this->createMock(ImageExtractor::class);
        $this->localiser = $this->createMock(ImageLocaliser::class);
        $this->updater = $this->createMock(ContentImageUpdater::class);

        $this->command = new LocaliseImagesCommand(
            $this->em,
            $this->extractor,
            $this->localiser,
            $this->updater
        );

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithDryRun(): void
    {
        // Mock different repositories for each entity type
        $imageRepo  = $this->createMock(DummyRepo::class);
        $pageRepo   = $this->createMock(DummyRepo::class);
        $seriesRepo = $this->createMock(DummyRepo::class);

        // Image entity mock (single field)
        $imageEntity = $this->createMock(Image::class);
        $imageEntity->method('getFilename')->willReturn('https://example.com/img1.jpg');
        $imageRepo->method('getAll')->willReturn([$imageEntity]);

        // Page entity mock (content field)
        $pageEntity = $this->createMock(Page::class);
        $pageEntity->method('getContent')->willReturn('<img src="https://example.com/foo.jpg" />');
        $pageRepo->method('getAll')->willReturn([$pageEntity]);

        // Series entity mock (description field)
        $seriesEntity = $this->createMock(Series::class);
        $seriesEntity->method('getDescription')->willReturn('<img src="https://example.com/bar.jpg" />');
        $seriesRepo->method('getAll')->willReturn([$seriesEntity]);

        // Configure EntityManager to return correct repo depending on class name
        $this->em->method('getRepository')->willReturnMap([
            [Image::class, $imageRepo],
            [Page::class, $pageRepo],
            [Series::class, $seriesRepo],
        ]);

        // Extractor called twice (for Page and Series, both non-single)
        $this->extractor->expects($this->exactly(2))
            ->method('extractFromContent')
            ->willReturn(['https://example.com/foo.jpg']);

        // Localiser never called in dry-run
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
        // Mock different repositories again
        $imageRepo  = $this->createMock(DummyRepo::class);
        $pageRepo   = $this->createMock(DummyRepo::class);
        $seriesRepo = $this->createMock(DummyRepo::class);

        // Only test Page entity real logic
        $entity = $this->createMock(Page::class);
        $entity->method('getContent')->willReturn('<img src="https://example.com/image.jpg" />');
        $pageRepo->method('getAll')->willReturn([$entity]);
        $imageRepo->method('getAll')->willReturn([]); // no images
        $seriesRepo->method('getAll')->willReturn([]);

        $this->em->method('getRepository')->willReturnMap([
            [Image::class, $imageRepo],
            [Page::class, $pageRepo],
            [Series::class, $seriesRepo],
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
                    'source' => ['https://example.com/image.jpg'],
                    'destination' => ['/var/www/public/imgs/image.jpg']
                ],
                true
            );

        $exitCode = $this->commandTester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('done.', $output);
    }
}
