<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Analytics;

use Doctrine\DBAL\Connection;
use Inachis\Repository\AnalyticsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add this to cron such as *\/5 * * * * php /path/to/bin/console inachis:analytics:aggregate
 */
#[AsCommand(
    name: 'inachis:analytics:aggregate',
    description: 'Aggregate analytics data from log files',
)]
class AggregateAnalyticsCommand extends Command
{
    public function __construct(private Connection $db, private AnalyticsRepository $analyticsRepository) {
		parent::__construct();
	}

	/**
	 * Processes log files and aggregates analytics data
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = __DIR__ . '/../../../var/analytics';

        if (!is_dir($dir)) {
            return Command::SUCCESS;
        }

        $files = glob($dir . '/*-*.log');
        foreach ($files as $file) {
			$output->writeln(sprintf('Processing <info>%s</info> ...', basename($file)));
			if (str_contains($file, '/analytics-')) {
				$this->processFile($file);
			} elseif (str_contains($file, '/error-')) {
				$this->processErrorFile($file);
			}

			$output->writeln(sprintf('Processed %s', basename($file)));

			rename($file, $file . '.processed');
        }
        return Command::SUCCESS;
    }

	/**
	 * Processes a single log file and aggregates analytics data
	 *
	 * @param string $file
	 */
    private function processFile(string $file): void
    {
        $handle = fopen($file, 'r');

        $pageViews = [];
        $uniqueVisitors = [];
		$referrers = [];

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);

            if (!$data || !isset($data['path'], $data['date'])) {
                continue;
            }

			$path = $data['path'];
            $date = $data['date'];
            $visitor = $data['visitor'] ?? null;

            $key = $path . '|' . $date;
            $pageViews[$key] = ($pageViews[$key] ?? 0) + 1;
			if ($visitor) {
                $uniqueVisitors[$date][$visitor] = true;
            }

			$ref = $data['ref'] ?? null;
			if ($ref) {
				$key = $ref . '|' . $path . '|' . $date;
				$referrers[$key] = ($referrers[$key] ?? 0) + 1;
			}
        }

        fclose($handle);

        foreach ($pageViews as $key => $views) {
            [$path, $date] = explode('|', $key);

            $this->db->executeStatement(
                '
                INSERT INTO analytics_page_view (path, date, views)
                VALUES (:path, :date, :views)
                ON DUPLICATE KEY UPDATE views = views + :views
                ',
                [
                    'path' => $path,
                    'date' => $date,
                    'views' => $views,
                ]
            );
        }

		foreach ($uniqueVisitors as $date => $visitors) {
            foreach (array_keys($visitors) as $visitorHash) {
                $this->db->executeStatement(
                    '
                    INSERT IGNORE INTO analytics_unique_visitor (visitor_hash, date)
                    VALUES (:hash, :date)
                    ',
                    [
                        'hash' => $visitorHash,
                        'date' => $date,
                    ]
                );
            }
        }

		foreach ($referrers as $key => $hits) {
			[$domain, $path, $date] = explode('|', $key);

			$this->db->executeStatement(
				'
				INSERT INTO analytics_referrer (domain, path, date, hits)
				VALUES (:domain, :path, :date, :hits)
				ON DUPLICATE KEY UPDATE hits = hits + :hits
				',
				[
					'domain' => $domain,
					'path' => $path,
					'date' => $date,
					'hits' => $hits,
				]
			);
		}
    }

	/**
	 * Processes 404 files and aggregates 404 data
	 *
	 * @param string $file
	 */
	private function processErrorFile(string $file): void
	{
		$handle = fopen($file, 'r');

		if (!$handle) {
			return;
		}

		$counts = [];

		while (($line = fgets($handle)) !== false) {
			$data = json_decode($line, true);

			if (!$data || !isset($data['path'], $data['date'], $data['code'])) {
				continue;
			}

			$key = $data['path'] . '|' . $data['date'] . '|' . $data['code'];
			$counts[$key] = ($counts[$key] ?? 0) + 1;
		}

		fclose($handle);

		foreach ($counts as $key => $hits) {
			[$path, $date, $code] = explode('|', $key);

			$this->db->executeStatement(
				'
				INSERT INTO analytics_errors (path, date, code, hits)
				VALUES (:path, :date, :code, :hits)
				ON DUPLICATE KEY UPDATE hits = hits + :hits
				',
				[
					'path' => $path,
					'date' => $date,
					'code' => $code,
					'hits' => $hits,
				]
			);
		}
	}
}
