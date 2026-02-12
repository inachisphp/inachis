<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Entity\PasswordResetRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to purge expired password reset requests.
 */
#[AsCommand(
    name: 'app:purge-expired-reset-requests',
    description: 'Purges all expired password reset requests.',
)]
class PurgeExpiredResetRequestsCommand extends Command
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    /**
     * Executes the command.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var \Inachis\Repository\PasswordResetRequestRepository $passwordResetRequestRepository */
        $passwordResetRequestRepository = $this->entityManager->getRepository(PasswordResetRequest::class);
        $count = $passwordResetRequestRepository->purgeExpiredHashes();
        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf('Deleted %d expired password reset requests.', $count));
        return Command::SUCCESS;
    }
}
