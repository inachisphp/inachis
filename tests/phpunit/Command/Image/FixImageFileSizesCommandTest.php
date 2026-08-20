<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Image;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Image\FixImageFileSizesCommand;
use Inachis\Repository\Media\ImageRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FixImageFileSizesCommandTest extends TestCase
{
    private string $workingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDirectory = getcwd();

        $directory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'inachis-fix-image-filesizes-'
            .uniqid('', true);

        mkdir($directory.'/public/imgs', 0777, true);

        chdir($directory);
    }

    protected function tearDown(): void
    {
        chdir($this->workingDirectory);

        parent::tearDown();
    }

    #[Test]
    public function itUpdatesTheFilesizeForExistingImages(): void
    {
        $filename = 'existing-image.jpg';
        $contents = 'This is test image content.';
        $expectedSize = strlen($contents);

        file_put_contents(
            getcwd().'/public/imgs/'.$filename,
            $contents,
        );

        $image = $this->createImageDouble($filename);

        $repository = $this->createMock(ImageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$image]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixImageFileSizesCommand(
            entityManager: $entityManager,
            imageRepository: $repository,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame($expectedSize, $image->filesize);
        self::assertStringContainsString(
            "Updated: {$filename} → {$expectedSize} bytes",
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Checked: 1',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Updated: 1',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Missing files: 0',
            $tester->getDisplay(),
        );
    }

    #[Test]
    public function itReportsMissingImages(): void
    {
        $filename = 'missing-image.jpg';
        $image = $this->createImageDouble($filename);

        $repository = $this->createMock(ImageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$image]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixImageFileSizesCommand(
            entityManager: $entityManager,
            imageRepository: $repository,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNull($image->filesize);
        self::assertStringContainsString(
            "Missing file: {$filename}",
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Checked: 1',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Updated: 0',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Missing files: 1',
            $tester->getDisplay(),
        );
    }

    #[Test]
    public function itReportsDirectoriesAsMissingFiles(): void
    {
        $filename = 'image-directory';

        mkdir(getcwd().'/public/imgs/'.$filename);

        $image = $this->createImageDouble($filename);

        $repository = $this->createMock(ImageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$image]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixImageFileSizesCommand(
            entityManager: $entityManager,
            imageRepository: $repository,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNull($image->filesize);
        self::assertStringContainsString(
            "Missing file: {$filename}",
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Checked: 1',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Updated: 0',
            $tester->getDisplay(),
        );
        self::assertStringContainsString(
            'Missing files: 1',
            $tester->getDisplay(),
        );
    }

    #[Test]
    public function itProcessesMultipleImages(): void
    {
        $existingFilename = 'existing.jpg';
        $missingFilename = 'missing.jpg';

        $contents = 'image data';

        file_put_contents(
            getcwd().'/public/imgs/'.$existingFilename,
            $contents,
        );

        $existingImage = $this->createImageDouble($existingFilename);
        $missingImage = $this->createImageDouble($missingFilename);

        $repository = $this->createMock(ImageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([
                $existingImage,
                $missingImage,
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixImageFileSizesCommand(
            entityManager: $entityManager,
            imageRepository: $repository,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(strlen($contents), $existingImage->filesize);
        self::assertNull($missingImage->filesize);

        $display = $tester->getDisplay();

        self::assertStringContainsString(
            'Checked: 2',
            $display,
        );
        self::assertStringContainsString(
            'Updated: 1',
            $display,
        );
        self::assertStringContainsString(
            'Missing files: 1',
            $display,
        );
    }

    #[Test]
    public function itHandlesAnEmptyImageRepository(): void
    {
        $repository = $this->createMock(ImageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixImageFileSizesCommand(
            entityManager: $entityManager,
            imageRepository: $repository,
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $tester->getDisplay();

        self::assertStringContainsString(
            'Checked: 0',
            $display,
        );
        self::assertStringContainsString(
            'Updated: 0',
            $display,
        );
        self::assertStringContainsString(
            'Missing files: 0',
            $display,
        );
    }

    /**
     * Creates a lightweight object exposing the Image methods used by
     * FixImageFileSizesCommand.
     */
    private function createImageDouble(string $filename): object
    {
        return new class($filename) {
            public ?int $filesize = null;

            public function __construct(
                private readonly string $filename,
            ) {
            }

            public function getFilename(): string
            {
                return $this->filename;
            }

            public function setFilesize(int $filesize): void
            {
                $this->filesize = $filesize;
            }
        };
    }
}
