<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command\Analytics;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * Command to remove old analytics data and delete processed files
 * after 7 days
 * 
 * Add this to cron such as:
 * * * * * php /path/to/bin/console inachis:analytics:cleanup
 */
#[AsCommand(
    name: 'inachis:analytics:cleanup',
    description: 'Cleanup analytics data from log files',
)]
class CleanupAnalyticsCommand extends Command
{
    /**
     * @param string $projectDir
     * @param Connection $db
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private Connection $db,
    ) {
        parent::__construct();
    }

    /**
     * Keep last 90 days of analytics data
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = 90;
        $output->writeln('Removing analytics data older than ' . $days . ' days');
        $this->db->executeStatement(
            'DELETE FROM analytics_page_view
             WHERE date < DATE_SUB(CURDATE(), INTERVAL :days DAY)',
             [
                'days' => $days
             ]
        );
        $this->db->executeStatement(
            'DELETE FROM analytics_unique_visitor
             WHERE date < DATE_SUB(CURDATE(), INTERVAL :days DAY)',
             [
                'days' => $days
             ]
        );

        $output->writeln('Removing processed log files older than 7 days');
        $directory = $this->projectDir . '/var/analytics';
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name('*.processed')
            ->date('<= now - 7 days');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();

            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
                $output->writeln("Deleted: $filePath");
            }
        }

        return Command::SUCCESS;
    }
}