<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Command\Image;

use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Image\Migration\ImageMigrationApplier;
use Inachis\Service\Image\Migration\ImageMigrationPlanner;
use Inachis\Service\Image\Migration\ImageMigrationReporter;
use Inachis\Service\Image\Migration\ImageMigrationRollback;
use Inachis\Service\Image\Migration\ImageMigrationVerifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'inachis:images:migrate',
    description: 'Full image migration: rename, deduplicate, WebP optimise, update references, and verify',
)]
class ImageMigrationCommand extends Command
{
    private string $imageDir;
    private string $varDir;
    private string $backupDir;
    private string $planFile;
    private string $checkpointFile;
    private string $reportMdFile;
    private string $reportJsonFile;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private PageRepository $pageRepository,
        private SeriesRepository $seriesRepository,
        private ImageMigrationPlanner $planner,
        private ImageMigrationApplier $applier,
        private ImageMigrationRollback $rollbackService,
        private ImageMigrationVerifier $verifier,
        private ImageMigrationReporter $reporter,
    ) {
        parent::__construct();

        $this->imageDir = rtrim($this->projectDir, '/').'/public/imgs/';
        $this->varDir = rtrim($this->projectDir, '/').'/var/image-migration/';
        $this->backupDir = $this->varDir.'backups/';
        $this->planFile = $this->varDir.'plan.json';
        $this->checkpointFile = $this->varDir.'checkpoint.json';
        $this->reportMdFile = $this->varDir.'report.md';
        $this->reportJsonFile = $this->varDir.'report.json';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mode', InputArgument::REQUIRED, 'scan | apply | rollback | report | verify')
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Remove var/image-migration directory upon successful verification')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview migration without making changes')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force operation without prompts or bypassing stale plan checks')
            ->addOption('resume', null, InputOption::VALUE_NONE, 'Resume apply mode from last checkpoint')
            ->addOption('no-webp', null, InputOption::VALUE_NONE, 'Disable WebP conversion')
            ->addOption('no-dedup', null, InputOption::VALUE_NONE, 'Disable image deduplication')
            ->addOption('no-resize', null, InputOption::VALUE_NONE, 'Disable image resizing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $argumentMode = $input->getArgument('mode');
        $mode = strtolower(is_string($argumentMode) ? $argumentMode : '');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        $resume = (bool) $input->getOption('resume');
        $noWebp = (bool) $input->getOption('no-webp');
        $noDedup = (bool) $input->getOption('no-dedup');
        $noResize = (bool) $input->getOption('no-resize');

        return match ($mode) {
            'scan' => $this->scan($output, $noWebp, $noDedup, $noResize),
            'apply' => $this->apply($output, $dryRun, $force, $resume, $noWebp, $noDedup, $noResize),
            'rollback' => $this->rollback($output, $dryRun),
            'report' => $this->report($output),
            'verify' => $this->verify($input, $output),
            default => $this->invalidMode($output, $mode)
        };
    }

    private function scan(OutputInterface $output, bool $noWebp, bool $noDedup, bool $noResize): int
    {
        $plan = $this->planner->generatePlan($this->imageDir, $noWebp, $noDedup, $noResize);

        $this->ensureDirectoriesExist();
        file_put_contents($this->planFile, (string) json_encode($plan, JSON_PRETTY_PRINT));
        $this->reporter->writeReports($plan, [], $this->reportMdFile, $this->reportJsonFile);

        /** @var list<mixed> $images */
        $images = is_array($plan['images'] ?? null) ? $plan['images'] : [];
        /** @var list<mixed> $duplicates */
        $duplicates = is_array($plan['duplicates'] ?? null) ? $plan['duplicates'] : [];
        /** @var list<mixed> $unused */
        $unused = is_array($plan['unused'] ?? null) ? $plan['unused'] : [];
        /** @var list<mixed> $broken */
        $broken = is_array($plan['broken'] ?? null) ? $plan['broken'] : [];

        $output->writeln('<info>Scan complete. Plan saved to var/image-migration/plan.json</info>');
        $output->writeln(sprintf('Images scanned: <comment>%d</comment>', count($images)));
        $output->writeln(sprintf('Duplicates found: <comment>%d</comment>', count($duplicates)));
        $output->writeln(sprintf('Unused images: <comment>%d</comment>', count($unused)));
        $output->writeln(sprintf('Broken references: <comment>%d</comment>', count($broken)));

        return Command::SUCCESS;
    }

    private function apply(
        OutputInterface $output,
        bool $dryRun,
        bool $force,
        bool $resume,
        bool $noWebp,
        bool $noDedup,
        bool $noResize,
    ): int {
        if (!file_exists($this->planFile)) {
            $output->writeln('<error>No migration plan found. Run scan first.</error>');

            return Command::FAILURE;
        }

        /** @var array<string, mixed> $rawPlan */
        $rawPlan = json_decode((string) file_get_contents($this->planFile), true) ?? [];
        $plan = $this->assertPlanShape($rawPlan);

        if (!$force && $this->applier->isPlanStale($plan)) {
            $output->writeln('<error>Migration plan is stale! Repository entity counts have changed since scan. Re-run scan or pass --force.</error>');

            return Command::FAILURE;
        }

        if ($dryRun) {
            $pageCount = count($this->pageRepository->findAll());
            $seriesCount = count($this->seriesRepository->findAll());
            $this->reporter->executeDryRun($output, $plan, $pageCount, $seriesCount);

            return Command::SUCCESS;
        }

        $checkpoint = $this->loadCheckpoint($resume);
        $this->ensureDirectoriesExist();

        $saveCheckpointCallback = fn (array $cp) => file_put_contents($this->checkpointFile, (string) json_encode($cp, JSON_PRETTY_PRINT));

        $appliedStats = $this->applier->applyPlan(
            $plan,
            $checkpoint,
            $this->imageDir,
            $this->backupDir,
            $output,
            $noWebp,
            $noDedup,
            $noResize,
            $saveCheckpointCallback,
        );

        if (file_exists($this->checkpointFile)) {
            @unlink($this->checkpointFile);
        }
        $this->reporter->writeReports($plan, $appliedStats, $this->reportMdFile, $this->reportJsonFile);

        $output->writeln('<info>Migration successfully applied!</info>');

        return Command::SUCCESS;
    }

    private function rollback(OutputInterface $output, bool $dryRun): int
    {
        if (!file_exists($this->planFile)) {
            $output->writeln('<error>No plan file found to rollback from.</error>');

            return Command::FAILURE;
        }

        /** @var array<string, mixed> $rawPlan */
        $rawPlan = json_decode((string) file_get_contents($this->planFile), true) ?? [];
        $plan = $this->assertPlanShape($rawPlan);

        if ($dryRun) {
            $output->writeln('<comment>[DRY RUN] Previewing Rollback from var/image-migration/backups/...</comment>');
            $images = $plan['images'] ?? [];
            foreach ($images as $img) {
                $output->writeln(sprintf('RESTORE %s → %s', $img['newFilename'], $img['oldFilename']));
            }

            return Command::SUCCESS;
        }

        $output->writeln('<info>Rolling back image migration from backups...</info>');
        $this->rollbackService->rollbackPlan($plan, $this->imageDir, $this->backupDir, $output);

        $output->writeln('<info>Rollback complete.</info>');

        return Command::SUCCESS;
    }

    private function verify(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->verifier->verify($this->imageDir, $output);
        $shouldClean = (bool) $input->getOption('clean');

        if ($result) {
            if ($shouldClean) {
                $output->writeln('<info>Verification successful. Cleaning up temporary migration artifacts...</info>');
                $this->removeDirectory($this->varDir);
                $output->writeln('<info>✓ Removed var/image-migration/</info>');
            } else {
                $output->writeln('<comment>Tip: Run verify with --clean to purge backup files and migration logs (var/image-migration/).</comment>');
            }

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    /**
     * Recursively remove a directory and its contents safely.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    private function report(OutputInterface $output): int
    {
        if (file_exists($this->reportMdFile)) {
            $output->writeln(file_get_contents($this->reportMdFile) ?: 'Empty report.');

            return Command::SUCCESS;
        }

        if (file_exists($this->planFile)) {
            /** @var array<string, mixed> $rawPlan */
            $rawPlan = json_decode((string) file_get_contents($this->planFile), true) ?? [];
            $plan = $this->assertPlanShape($rawPlan);
            $output->writeln($this->reporter->generateReportMarkdown($plan, []));

            return Command::SUCCESS;
        }

        $output->writeln('<error>No plan or report file found. Run scan first.</error>');

        return Command::FAILURE;
    }

    /**
     * Load checkpoint state from disk.
     *
     * @return array{
     *     imageIndex: int,
     *     pageIndex: int,
     *     seriesIndex: int,
     *     completedImageIds: list<string>,
     *     completedPageIds: list<string>,
     *     completedSeriesIds: list<string>
     * }
     */
    private function loadCheckpoint(bool $resume): array
    {
        if ($resume && file_exists($this->checkpointFile)) {
            /** @var array<string, mixed> $data */
            $data = json_decode((string) file_get_contents($this->checkpointFile), true) ?? [];

            /** @var list<mixed> $completedImageIds */
            $completedImageIds = is_array($data['completedImageIds'] ?? null) ? $data['completedImageIds'] : [];
            /** @var list<mixed> $completedPageIds */
            $completedPageIds = is_array($data['completedPageIds'] ?? null) ? $data['completedPageIds'] : [];
            /** @var list<mixed> $completedSeriesIds */
            $completedSeriesIds = is_array($data['completedSeriesIds'] ?? null) ? $data['completedSeriesIds'] : [];

            return [
                'imageIndex' => is_numeric($data['imageIndex'] ?? null) ? (int) $data['imageIndex'] : 0,
                'pageIndex' => is_numeric($data['pageIndex'] ?? null) ? (int) $data['pageIndex'] : 0,
                'seriesIndex' => is_numeric($data['seriesIndex'] ?? null) ? (int) $data['seriesIndex'] : 0,
                'completedImageIds' => array_map(static fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $completedImageIds),
                'completedPageIds' => array_map(static fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $completedPageIds),
                'completedSeriesIds' => array_map(static fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $completedSeriesIds),
            ];
        }

        return [
            'imageIndex' => 0,
            'pageIndex' => 0,
            'seriesIndex' => 0,
            'completedImageIds' => [],
            'completedPageIds' => [],
            'completedSeriesIds' => [],
        ];
    }

    /**
     * Asserts and types the migration plan shape loaded from disk.
     *
     * @param array<string, mixed> $plan
     *
     * @return array{
     *     images?: list<array{
     *         id: string,
     *         oldFilename: string,
     *         newFilename: string,
     *         oldFilesize: int,
     *         estimatedFilesize: int,
     *         isDuplicate: bool,
     *         needsResize: bool,
     *         convertToWebp: bool,
     *         origWidth: int,
     *         origHeight: int,
     *         origMegapixels: float,
     *         targetWidth: int,
     *         targetHeight: int,
     *         targetMegapixels: float,
     *         pixelReductionPercent: float|int
     *     }>,
     *     duplicates?: list<array{
     *         duplicateId: string,
     *         canonicalId: string,
     *         duplicateFilename: string,
     *         canonicalFilename: string,
     *         pixelHash?: string
     *     }>,
     *     broken?: list<array{entity: string, id: string, filename: string}>,
     *     unused?: list<array{filename: string, size: int}>,
     *     stats?: array{
     *         totalOriginalBytes?: int,
     *         totalEstimatedFinalBytes?: int,
     *         totalOriginalMegapixels?: float,
     *         totalFinalMegapixels?: float
     *     },
     *     metadata?: array{
     *         imageCount?: int,
     *         pageCount?: int,
     *         seriesCount?: int
     *     },
     *     contentReplacements?: array<string, string>,
     *     entityBackups?: array{
     *         images?: array<string, array{
     *             title?: string,
     *             description?: string|null,
     *             authorId?: string|null,
     *             createdAt?: string|null,
     *             updatedAt?: string|null,
     *             filename: string,
     *             filesize: int,
     *             checksum: string,
     *             dimensionX: int,
     *             dimensionY: int,
     *             filetype?: string
     *         }>,
     *         pages?: array<string, array{featureImageId?: string|null}>,
     *         series?: array<string, array{imageId?: string|null}>
     *     }
     * }
     */
    private function assertPlanShape(array $plan): array
    {
        /** @var array{
         *     images?: list<array{
         *         id: string,
         *         oldFilename: string,
         *         newFilename: string,
         *         oldFilesize: int,
         *         estimatedFilesize: int,
         *         isDuplicate: bool,
         *         needsResize: bool,
         *         convertToWebp: bool,
         *         origWidth: int,
         *         origHeight: int,
         *         origMegapixels: float,
         *         targetWidth: int,
         *         targetHeight: int,
         *         targetMegapixels: float,
         *         pixelReductionPercent: float|int
         *     }>,
         *     duplicates?: list<array{
         *         duplicateId: string,
         *         canonicalId: string,
         *         duplicateFilename: string,
         *         canonicalFilename: string,
         *         pixelHash?: string
         *     }>,
         *     broken?: list<array{entity: string, id: string, filename: string}>,
         *     unused?: list<array{filename: string, size: int}>,
         *     stats?: array{
         *         totalOriginalBytes?: int,
         *         totalEstimatedFinalBytes?: int,
         *         totalOriginalMegapixels?: float,
         *         totalFinalMegapixels?: float
         *     },
         *     metadata?: array{
         *         imageCount?: int,
         *         pageCount?: int,
         *         seriesCount?: int
         *     },
         *     contentReplacements?: array<string, string>,
         *     entityBackups?: array{
         *         images?: array<string, array{
         *             title?: string,
         *             description?: string|null,
         *             authorId?: string|null,
         *             createdAt?: string|null,
         *             updatedAt?: string|null,
         *             filename: string,
         *             filesize: int,
         *             checksum: string,
         *             dimensionX: int,
         *             dimensionY: int,
         *             filetype?: string
         *         }>,
         *         pages?: array<string, array{featureImageId?: string|null}>,
         *         series?: array<string, array{imageId?: string|null}>
         *     }
         * } $typedPlan */
        $typedPlan = $plan;

        return $typedPlan;
    }

    private function ensureDirectoriesExist(): void
    {
        if (!is_dir($this->varDir)) {
            mkdir($this->varDir, 0777, true);
        }
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    private function invalidMode(OutputInterface $output, string $mode): int
    {
        $output->writeln(sprintf('<error>Invalid mode "%s". Supported modes: scan, apply, rollback, report, verify</error>', $mode));

        return Command::FAILURE;
    }
}
