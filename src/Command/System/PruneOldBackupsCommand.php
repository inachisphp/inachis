<?php

declare(strict_types=1);

namespace Inachis\Command\System;

use Inachis\Message\PruneOldBackupsMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Example usage:
 * 0 2 * * * php /path/to/inachis/bin/console inachis:backups:prune --days=30
 */
#[AsCommand(
    name: 'inachis:backups:prune',
    description: 'Prune database backup files older than a specified number of days.'
)]
class PruneOldBackupsCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Retention limit in days', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $this->bus->dispatch(new PruneOldBackupsMessage(
            retentionDays: $days,
            backupDir: $this->projectDir . '/var/backups'
        ));

        $io->success(sprintf('Dispatched cleanup task for backups older than %d days.', $days));

        return Command::SUCCESS;
    }
}
