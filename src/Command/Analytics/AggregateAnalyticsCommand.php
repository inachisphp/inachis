<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Analytics;

use Doctrine\DBAL\Connection;
use Inachis\Repository\Analytics\AnalyticsRepository;
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
			} elseif (str_contains($file, '/subscriber-')) {
                $this->processSubscriberFile($file);
            } elseif (str_contains($file, '/bot-')) {
                $this->processBotFile($file);
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
        if (!$handle) {
            return;
        }

        $pageViews = [];
        $uniqueVisitors = [];
		$referrers = [];
        $regionHits = []; // Array of [date => [countryCode => [name => string, hits => int]]]

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);

            if (!$data || !isset($data['path'], $data['date'])) {
                continue;
            }

			$path = $this->normalisePath($data['path']);
            $date = $data['date'];
            $visitor = $data['visitor'] ?? null;
            $ip = $data['ip'] ?? null;

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

            // Resolve country/region for this IP
            if ($ip) {
                $country = $this->resolveIpToCountry($ip);
                $code = $country['code'];
                $name = $country['name'];

                if (!isset($regionHits[$date][$code])) {
                    $regionHits[$date][$code] = [
                        'name' => $name,
                        'hits' => 0,
                    ];
                }
                $regionHits[$date][$code]['hits']++;
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

        // Save region/country stats
        foreach ($regionHits as $date => $countries) {
            foreach ($countries as $code => $countryData) {
                $this->db->executeStatement(
                    '
                    INSERT INTO analytics_regions (country_code, country_name, date, hits)
                    VALUES (:code, :name, :date, :hits)
                    ON DUPLICATE KEY UPDATE hits = hits + :hits
                    ',
                    [
                        'code' => $code,
                        'name' => $countryData['name'],
                        'date' => $date,
                        'hits' => $countryData['hits'],
                    ]
                );
            }
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
            if ($this->shouldIgnoreError($data['path'])) {
                continue;
            }

            $key = $this->normalisePath($data['path']) . '|' . $data['date'] . '|' . $data['code'];
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

    /**
     * Processes RSS subscriber log files
     *
     * @param string $file
     */
    private function processSubscriberFile(string $file): void
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return;
        }

        $aggregators = []; // [path => [aggregatorName => max_subscriber_count]]
        $individuals = []; // [path => [visitorId => true]]
        $date = null;

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);
            if (!$data || !isset($data['path'], $data['date'])) {
                continue;
            }

            $path = $this->normalisePath($data['path']);
            $date = $data['date'];
            $visitor = $data['visitor'] ?? '';
            $ua = $data['ua'] ?? '';

            // Detect feed aggregators
            $aggName = null;
            if (stripos($ua, 'Feedly') !== false) { $aggName = 'Feedly'; }
            elseif (stripos($ua, 'Feedbin') !== false) { $aggName = 'Feedbin'; }
            elseif (stripos($ua, 'NewsBlur') !== false) { $aggName = 'NewsBlur'; }
            elseif (stripos($ua, 'Bloglovin') !== false) { $aggName = 'Bloglovin'; }
            elseif (stripos($ua, 'Blogtrottr') !== false) { $aggName = 'Blogtrottr'; }
            elseif (stripos($ua, 'Superfeedr') !== false) { $aggName = 'Superfeedr'; }
            elseif (stripos($ua, 'WordPress') !== false) { $aggName = 'WordPress'; }
            elseif (stripos($ua, 'FeedFetcher') !== false) { $aggName = 'FeedFetcher'; }

            if ($aggName) {
                $count = 1;
                if (preg_match('/(\d+)\s+subscriber/i', $ua, $matches)) {
                    $count = (int) $matches[1];
                }
                $aggregators[$path][$aggName] = max($aggregators[$path][$aggName] ?? 0, $count);
            } elseif ($visitor) {
                $individuals[$path][$visitor] = true;
            }
        }

        fclose($handle);

        if (!$date) {
            return;
        }

        // Combine standard aggregators + unique individual reader visitor hashes
        $allPaths = array_unique(array_merge(array_keys($aggregators), array_keys($individuals)));
        foreach ($allPaths as $path) {
            $totalSubscribers = array_sum($aggregators[$path] ?? []) + count($individuals[$path] ?? []);

            $this->db->executeStatement(
                '
                INSERT INTO analytics_subscribers (path, date, subscribers)
                VALUES (:path, :date, :subscribers)
                ON DUPLICATE KEY UPDATE subscribers = :subscribers
                ',
                [
                    'path' => $path,
                    'date' => $date,
                    'subscribers' => $totalSubscribers,
                ]
            );
        }
    }

    /**
     * Processes bot log files and aggregates bot traffic by user-agent per day
     *
     * @param string $file
     */
    private function processBotFile(string $file): void
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return;
        }

        $counts = []; // [ua|date => hits]

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);

            if (!$data || !isset($data['ua'], $data['date'])) {
                continue;
            }

            $ua = mb_substr(trim($data['ua']), 0, 255);
            $date = $data['date'];
            $key = $ua . '|' . $date;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        fclose($handle);

        foreach ($counts as $key => $hits) {
            [$ua, $date] = explode('|', $key, 2);

            $this->db->executeStatement(
                '
                INSERT INTO analytics_bots (user_agent, date, hits)
                VALUES (:ua, :date, :hits)
                ON DUPLICATE KEY UPDATE hits = hits + :hits
                ',
                [
                    'ua'   => $ua,
                    'date' => $date,
                    'hits' => $hits,
                ]
            );
        }
    }

    /**
     * Resolves an IP to its Country Code and Country Name using a cache-backed GeoIP lookup
     *
     * @param string $ip
     * @return array{code: string, name: string}
     */
    private function resolveIpToCountry(string $ip): array
    {
        // 1. Identify local/private/empty IPs
        if (
            $ip === '127.0.0.1' ||
            $ip === '::1' ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
        ) {
            return ['code' => 'Local', 'name' => 'Local Network'];
        }

        // 2. Query cache database
        try {
            $cached = $this->db->fetchAssociative(
                'SELECT country_code, country_name FROM analytics_ip_cache WHERE ip = :ip LIMIT 1',
                ['ip' => $ip]
            );

            if ($cached) {
                return [
                    'code' => $cached['country_code'],
                    'name' => $cached['country_name']
                ];
            }
        } catch (\Exception $e) {
            // Ignore DB errors
        }

        // 3. Resolve using ip-api.com (timeout: 2 seconds)
        $code = 'Unknown';
        $name = 'Unknown';

        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2.0,
                    'user_agent' => 'InachisAnalytics/1.0'
                ]
            ]);
            $response = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,countryCode', false, $ctx);
            if ($response) {
                $res = json_decode($response, true);
                if ($res && isset($res['status']) && $res['status'] === 'success') {
                    $code = $res['countryCode'] ?? 'Unknown';
                    $name = $res['country'] ?? 'Unknown';
                }
            }
        } catch (\Exception $e) {
            // Fallback to unknown if API fails
        }

        // 4. Save to cache
        try {
            $this->db->executeStatement(
                '
                INSERT IGNORE INTO analytics_ip_cache (ip, country_code, country_name, created_at)
                VALUES (:ip, :code, :name, NOW())
                ',
                [
                    'ip' => $ip,
                    'code' => $code,
                    'name' => $name
                ]
            );
        } catch (\Exception $e) {
            // Ignore DB errors
        }

        return ['code' => $code, 'name' => $name];
    }

    /**
     * Ensures no path is stored with more than 255 characters
     * 
     * @param string $path The path to normalise
     * @return string The normalised path
     */
    private function normalisePath(string $path): string
    {
        return mb_substr(trim($path), 0, 255);
    }

    /**
     * Exclude obvious vulnerability scans from statistics - they should
     * be dealt with through other means
     * 
     * @param string $path The path to check for ignoring
     * @return bool Should the current path be ignored
     */
    private function shouldIgnoreError(string $path): bool
    {
        $patterns = [
            '.env',
            'phpinfo',
            'wp-admin',
            'wp-login',
            'vendor/phpunit',
            'server-status',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($path, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
