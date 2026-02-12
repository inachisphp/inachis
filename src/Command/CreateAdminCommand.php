<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Create an admin user
 */
#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new administrator account for your site',
)]
class CreateAdminCommand extends Command
{
    /**
     * Entity manager
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Password hasher
     */
    protected UserPasswordHasherInterface $passwordHasher;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    /**
     * Configure the command
     */
    protected function configure(): void
    {
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $normalizeToString = function (mixed $value = null): string {
            if ($value === null) {
                throw new InvalidArgumentException('This value cannot be empty');
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException('Value must be a string.');
            }
            $trimmed = trim($value);
            if ($trimmed === '') {
                throw new InvalidArgumentException('This value cannot be empty');
            }

            return $trimmed;
        };

        $question = new Question('Please enter a new username: ');
        $question->setValidator($normalizeToString);
        /** @var string $username */
        $username = $helper->ask($input, $output, $question);

        $question = new Question('Please enter an email address for the user: ');
        $question->setValidator($normalizeToString);
        /** @var string $emailAddress */
        $emailAddress = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a password for the user: ');
        $question->setValidator($normalizeToString);
        $question->setHidden(true);
        $question->setMaxAttempts(2);
        /** @var string $plaintextPassword */
        $plaintextPassword = $helper->ask($input, $output, $question);

        $user = new User(
            $username,
            $plaintextPassword,
            $emailAddress
        );
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setDisplayName($username);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User %s created', ($user->getUsername())));

        return Command::SUCCESS;
    }
}
