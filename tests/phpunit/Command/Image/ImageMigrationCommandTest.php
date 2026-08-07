<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Image;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Image\ImageMigrationCommand;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Service\Image\Migration\ImageMigrationApplier;
use Inachis\Service\Image\Migration\ImageMigrationPlanner;
use Inachis\Service\Image\Migration\ImageMigrationReporter;
use Inachis\Service\Image\Migration\ImageMigrationRollback;
use Inachis\Service\Image\Migration\ImageMigrationVerifier;
use Inachis\Service\Image\Migration\ImageProcessor;
use Inachis\Service\Image\Migration\MarkdownImageRewriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ImageMigrationCommandTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ImageRepository&MockObject $imageRepository;
    private PageRepository&MockObject $pageRepository;
    private SeriesRepository&MockObject $seriesRepository;

    private ImageProcessor $imageProcessor;
    private MarkdownImageRewriter $markdownRewriter;
    private ImageMigrationPlanner $planner;
    private ImageMigrationApplier $applier;
    private ImageMigrationRollback $rollbackService;
    private ImageMigrationVerifier $verifier;
    private ImageMigrationReporter $reporter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageRepository = $this->createMock(ImageRepository::class);
        $this->pageRepository = $this->createMock(PageRepository::class);
        $this->seriesRepository = $this->createMock(SeriesRepository::class);

        $connection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $this->imageProcessor = new ImageProcessor();
        $this->markdownRewriter = new MarkdownImageRewriter();
        $this->planner = new ImageMigrationPlanner(
            $this->imageRepository,
            $this->pageRepository,
            $this->seriesRepository,
            new AsciiSlugger(),
            $this->imageProcessor,
            $this->markdownRewriter,
        );
        $this->applier = new ImageMigrationApplier(
            $this->entityManager,
            $this->imageRepository,
            $this->pageRepository,
            $this->seriesRepository,
            $this->imageProcessor,
            $this->markdownRewriter,
        );
        $this->rollbackService = new ImageMigrationRollback(
            $this->entityManager,
            $this->imageRepository,
            $this->pageRepository,
            $this->seriesRepository,
            $this->markdownRewriter,
        );
        $this->verifier = new ImageMigrationVerifier(
            $this->imageRepository,
            $this->pageRepository,
            $this->seriesRepository,
            $this->markdownRewriter,
        );
        $this->reporter = new ImageMigrationReporter();
    }

    private function createCommand(): ImageMigrationCommand
    {
        return new ImageMigrationCommand(
            getcwd() ?: '.',
            $this->imageRepository,
            $this->pageRepository,
            $this->seriesRepository,
            $this->planner,
            $this->applier,
            $this->rollbackService,
            $this->verifier,
            $this->reporter,
        );
    }

    public function testExecuteFailsOnInvalidMode(): void
    {
        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $statusCode = $tester->execute(['mode' => 'invalid']);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertStringContainsString('Invalid mode "invalid"', $tester->getDisplay());
    }

    public function testScanModeCreatesPlanAndReport(): void
    {
        $this->imageRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->pageRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->seriesRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $statusCode = $tester->execute(['mode' => 'scan']);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString('Scan complete', $tester->getDisplay());
        $this->assertFileExists(getcwd().'/var/image-migration/plan.json');
        $this->assertFileExists(getcwd().'/var/image-migration/report.md');
        $this->assertFileExists(getcwd().'/var/image-migration/report.json');
    }

    public function testApplyDryRunPrintsPreviewWithoutModifying(): void
    {
        $this->imageRepository->method('findBy')->willReturn([]);
        $this->pageRepository->method('findBy')->willReturn([]);
        $this->seriesRepository->method('findBy')->willReturn([]);

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['mode' => 'scan']);

        $statusCode = $tester->execute(['mode' => 'apply', '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString('[DRY RUN] Previewing migration changes...', $tester->getDisplay());
        $this->assertStringContainsString('=== RENAME ===', $tester->getDisplay());
        $this->assertStringContainsString('=== STORAGE ===', $tester->getDisplay());
    }

    public function testReportModeDisplaysReport(): void
    {
        $this->imageRepository->method('findBy')->willReturn([]);
        $this->pageRepository->method('findBy')->willReturn([]);
        $this->seriesRepository->method('findBy')->willReturn([]);

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['mode' => 'scan']);

        $statusCode = $tester->execute(['mode' => 'report']);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString('Image Migration Report', $tester->getDisplay());
    }

    public function testRollbackDryRunPrintsRestorePreview(): void
    {
        $this->imageRepository->method('findBy')->willReturn([]);
        $this->pageRepository->method('findBy')->willReturn([]);
        $this->seriesRepository->method('findBy')->willReturn([]);

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['mode' => 'scan']);

        $statusCode = $tester->execute(['mode' => 'rollback', '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString('[DRY RUN] Previewing Rollback', $tester->getDisplay());
    }

    public function testVerifyModePassesOnEmptyRepository(): void
    {
        $this->imageRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->pageRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->seriesRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $statusCode = $tester->execute(['mode' => 'verify']);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertStringContainsString('0 images verified', $tester->getDisplay());
        $this->assertStringContainsString('no broken references', $tester->getDisplay());
    }
}
