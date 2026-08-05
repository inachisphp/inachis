<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Image\Migration;

use Symfony\Component\Console\Output\OutputInterface;

class ImageMigrationReporter
{
    /**
     * Write report.md and report.json files.
     *
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $appliedStats
     */
    public function writeReports(
        array $plan,
        array $appliedStats,
        string $reportMdFile,
        string $reportJsonFile
    ): void {
        $markdown = $this->generateReportMarkdown($plan, $appliedStats);
        file_put_contents($reportMdFile, $markdown);

        $jsonReport = [
            'timestamp' => date('c'),
            'metadata' => $plan['metadata'] ?? [],
            'options' => $plan['options'] ?? [],
            'stats' => $plan['stats'] ?? [],
            'appliedStats' => $appliedStats,
            'duplicates' => $plan['duplicates'] ?? [],
            'broken' => $plan['broken'] ?? [],
            'unused' => $plan['unused'] ?? [],
        ];
        file_put_contents($reportJsonFile, (string) json_encode($jsonReport, JSON_PRETTY_PRINT));
    }

    /**
     * Display Git-like structured dry-run output.
     *
     * @param array<string, mixed> $plan
     */
    public function executeDryRun(OutputInterface $output, array $plan, int $pageCount, int $seriesCount): void
    {
        $output->writeln('<comment>[DRY RUN] Previewing migration changes...</comment>');
        $output->writeln('');

        $images = $plan['images'] ?? [];
        $duplicates = $plan['duplicates'] ?? [];
        $broken = $plan['broken'] ?? [];
        $unused = $plan['unused'] ?? [];
        $stats = $plan['stats'] ?? [];

        $output->writeln('<info>=== RENAME ===</info>');
        foreach ($images as $img) {
            if (!$img['isDuplicate'] && $img['oldFilename'] !== $img['newFilename']) {
                $output->writeln(sprintf('%s → %s', $img['oldFilename'], $img['newFilename']));
            }
        }
        $output->writeln('');

        $output->writeln('<info>=== RESIZE & WEBP ===</info>');
        foreach ($images as $img) {
            if (!$img['isDuplicate'] && ($img['needsResize'] || $img['convertToWebp'])) {
                $output->writeln(sprintf(
                    '%s: %dx%d (%.2f MP) → %dx%d (%.2f MP) [-%s%% pixels]',
                    $img['oldFilename'],
                    $img['origWidth'],
                    $img['origHeight'],
                    $img['origMegapixels'],
                    $img['targetWidth'],
                    $img['targetHeight'],
                    $img['targetMegapixels'],
                    $img['pixelReductionPercent']
                ));
            }
        }
        $output->writeln('');

        $output->writeln('<info>=== DELETE DUPLICATE ===</info>');
        foreach ($duplicates as $dup) {
            $output->writeln(sprintf('%s → canonical ID %s', $dup['duplicateFilename'], $dup['canonicalId']));
        }
        $output->writeln('');

        $output->writeln('<info>=== CONTENT ===</info>');
        $output->writeln(sprintf('%d pages to update', $pageCount));
        $output->writeln(sprintf('%d series to update', $seriesCount));
        $output->writeln('');

        $origBytes = (int) ($stats['totalOriginalBytes'] ?? 0);
        $finalBytes = (int) ($stats['totalEstimatedFinalBytes'] ?? $origBytes);
        $savedBytes = max(0, $origBytes - $finalBytes);
        $savedPercent = $origBytes > 0 ? round(($savedBytes / $origBytes) * 100.0, 1) : 0.0;

        $output->writeln('<info>=== STORAGE ===</info>');
        $output->writeln(sprintf('Before: %s', $this->formatBytes($origBytes)));
        $output->writeln(sprintf('After: %s', $this->formatBytes($finalBytes)));
        $output->writeln(sprintf('Saved: %s (%.1f%%)', $this->formatBytes($savedBytes), $savedPercent));
        $output->writeln('');
        $output->writeln(sprintf('Unused images: %d | Broken references: %d', count($unused), count($broken)));
    }

    /**
     * Generate comprehensive report Markdown document.
     *
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $appliedStats
     */
    public function generateReportMarkdown(array $plan, array $appliedStats): string
    {
        $images = $plan['images'] ?? [];
        $duplicates = $plan['duplicates'] ?? [];
        $broken = $plan['broken'] ?? [];
        $unused = $plan['unused'] ?? [];
        $stats = $plan['stats'] ?? [];

        $origBytes = $appliedStats['totalOriginalBytes'] ?? ($stats['totalOriginalBytes'] ?? 0);
        $finalBytes = $appliedStats['totalFinalBytes'] ?? ($stats['totalEstimatedFinalBytes'] ?? $origBytes);
        $savedBytes = max(0, $origBytes - $finalBytes);
        $percentReduction = $origBytes > 0 ? round(($savedBytes / $origBytes) * 100.0, 2) : 0.0;

        $origMP = $stats['totalOriginalMegapixels'] ?? 0.0;
        $finalMP = $stats['totalFinalMegapixels'] ?? $origMP;
        $savedMP = max(0.0, $origMP - $finalMP);

        $md = "# Image Migration Report\n\n";
        $md .= "## Summary\n\n";
        $md .= sprintf("- **Images scanned**: %d\n", count($images));
        $md .= sprintf("- **Renamed**: %d\n", $appliedStats['renamedCount'] ?? count($images));
        $md .= sprintf("- **Converted to WebP**: %d\n", $appliedStats['webpCount'] ?? 0);
        $md .= sprintf("- **Resized**: %d\n", $appliedStats['resizedCount'] ?? 0);
        $md .= sprintf("- **Deduplicated**: %d\n", count($duplicates));
        $md .= sprintf("- **Unused images**: %d\n", count($unused));
        $md .= sprintf("- **Broken references**: %d\n", count($broken));
        $md .= sprintf("- **Original size**: %s\n", $this->formatBytes($origBytes));
        $md .= sprintf("- **Final size**: %s\n", $this->formatBytes($finalBytes));
        $md .= sprintf("- **Space saved**: %s (%s%% reduction)\n", $this->formatBytes($savedBytes), $percentReduction);
        $md .= sprintf("- **Total Megapixels Removed**: %.2f MP\n\n", $savedMP);

        // Top 20 Resized Images
        $resizedImages = array_filter($images, fn($i) => !empty($i['needsResize']));
        usort($resizedImages, fn($a, $b) => ($b['origMegapixels'] <=> $a['origMegapixels']));
        $topResized = array_slice($resizedImages, 0, 20);

        if (!empty($topResized)) {
            $md .= "## Top Resized Images\n\n";
            $md .= "| Filename | Original Dims | Target Dims | Pixel Reduction |\n";
            $md .= "| --- | --- | --- | --- |\n";
            foreach ($topResized as $r) {
                $md .= sprintf(
                    "| `%s` | %dx%d (%.2f MP) | %dx%d (%.2f MP) | -%s%% |\n",
                    $r['oldFilename'],
                    $r['origWidth'],
                    $r['origHeight'],
                    $r['origMegapixels'],
                    $r['targetWidth'],
                    $r['targetHeight'],
                    $r['targetMegapixels'],
                    $r['pixelReductionPercent']
                );
            }
            $md .= "\n";
        }

        // Top 20 WebP Conversions
        $webpImages = array_filter($images, fn($i) => !empty($i['convertToWebp']));
        usort($webpImages, fn($a, $b) => (($b['oldFilesize'] - $b['estimatedFilesize']) <=> ($a['oldFilesize'] - $a['estimatedFilesize'])));
        $topWebp = array_slice($webpImages, 0, 20);

        if (!empty($topWebp)) {
            $md .= "## Top WebP Conversions\n\n";
            $md .= "| Original Filename | New Filename | Original Size | Estimated Size | Saved |\n";
            $md .= "| --- | --- | --- | --- | --- |\n";
            foreach ($topWebp as $w) {
                $saving = max(0, $w['oldFilesize'] - $w['estimatedFilesize']);
                $md .= sprintf(
                    "| `%s` | `%s` | %s | %s | %s |\n",
                    $w['oldFilename'],
                    $w['newFilename'],
                    $this->formatBytes($w['oldFilesize']),
                    $this->formatBytes($w['estimatedFilesize']),
                    $this->formatBytes($saving)
                );
            }
            $md .= "\n";
        }

        if (!empty($duplicates)) {
            $md .= "## Deduplicated Images\n\n";
            $md .= "| Duplicate | Canonical | Pixel Hash |\n";
            $md .= "| --- | --- | --- |\n";
            foreach ($duplicates as $dup) {
                $md .= sprintf("| `%s` | `%s` | `%s` |\n", $dup['duplicateFilename'], $dup['canonicalFilename'], substr($dup['pixelHash'] ?? '', 0, 16) . '...');
            }
            $md .= "\n";
        }

        if (!empty($broken)) {
            $md .= "## Broken References\n\n";
            $md .= "| Entity | ID | Filename |\n";
            $md .= "| --- | --- | --- |\n";
            foreach ($broken as $b) {
                $md .= sprintf("| %s | %s | `%s` |\n", $b['entity'], $b['id'], $b['filename']);
            }
            $md .= "\n";
        }

        if (!empty($unused)) {
            $md .= "## Unused Images\n\n";
            $md .= "| Filename | Size |\n";
            $md .= "| --- | --- |\n";
            foreach ($unused as $u) {
                $md .= sprintf("| `%s` | %s |\n", $u['filename'], $this->formatBytes($u['size']));
            }
            $md .= "\n";
        }

        if (!empty($appliedStats['errors'])) {
            $md .= "## Failures & Errors\n\n";
            foreach ($appliedStats['errors'] as $err) {
                $md .= sprintf("- %s\n", $err);
            }
            $md .= "\n";
        }

        return $md;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));

        return sprintf('%.2f %s', $bytes / (1024 ** $i), $units[$i] ?? 'B');
    }
}
