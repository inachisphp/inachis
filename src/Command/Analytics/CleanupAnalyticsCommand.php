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
 * Add this to cron such as * * * * * php /path/to/bin/console inachis:analytics:cleanup
 */
#[AsCommand(
    name: 'inachis:analytics:cleanup',
    description: 'Cleanup analytics data from log files',
)]
class CleanupAnalyticsCommand extends Command
{
    public function __construct(
        private Connection $db
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // keep last 90 days
        $this->db->executeStatement(
            'DELETE FROM analytics_page_view
             WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)'
        );

        $this->db->executeStatement(
            'DELETE FROM analytics_unique_visitor
             WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)'
        );

        return Command::SUCCESS;
    }
}