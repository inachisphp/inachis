<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Repository\LoginActivityRepository;
use Inachis\Message\CleanupLoginActivityMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Advisable to add this to Windows Scheduler or Linux Cron
 * e.g.
 * 0 2 * * * /usr/bin/php /path/to/your/project/bin/console inachis:cleanup-login-activity
 */
#[AsCommand(
    name: 'inachis:cleanup-login-activity',
    description: 'Deletes old login activity, successful older than 12 months, failed older than 90 days.'
)]
class CleanupLoginActivityCommand extends Command
{
    /**
     * @param MessageBusInterface $messenger
     * @param EntityManagerInterface $entityManager
     * @param LoginActivityRepository $repository
     */
    public function __construct(
        protected MessageBusInterface $messenger,
        protected EntityManagerInterface $entityManager,
        protected LoginActivityRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview how many records would be deleted without actually deleting them.'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = 1000;

        $this->messenger->dispatch(new CleanupLoginActivityMessage($dryRun, $batchSize));

        $output->writeln('Cleanup job dispatched to Messenger.');
        $output->writeln('<info>Run `messenger:consume async` to process it.</info>');

        return Command::SUCCESS;
    }
}