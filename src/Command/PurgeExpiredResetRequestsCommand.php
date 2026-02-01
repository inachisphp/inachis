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

#[AsCommand(
    name: 'app:purge-expired-reset-requests',
    description: 'Purges all expired password reset requests.',
)]
class PurgeExpiredResetRequestsCommand extends Command
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->entityManager->getRepository(PasswordResetRequest::class)->purgeExpiredHashes();
        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf('Deleted %d expired password reset requests.', $count));
        return Command::SUCCESS;
    }
}
