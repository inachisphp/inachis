<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new administrator account for your site',
)]
class CreateAdminCommand extends Command
{
    protected $entityManager;
    protected $passwordHasher;

    /**
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
     * @return void
     */
    protected function configure(): void
    {
//        $this
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('username', null, InputOption::VALUE_NONE, 'Option description')
//        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $question = new Question('Please enter a new username: ');
        $question->setNormalizer(function (string $value): string {
            return $value ? trim($value) : '';
        });
        $username = $helper->ask($input, $output, $question);

        $question = new Question('Please enter an email address for the user: ');
        $question->setNormalizer(function (string $value): string {
            return $value ? trim($value) : '';
        });
        $emailAddress = $helper->ask($input, $output, $question);

        $question = new Question('Please enter a password for the user: ');
        $question->setNormalizer(function (string $value): string {
            return $value ? trim($value) : '';
        });
        $question->setValidator(function (string $value): string {
            if ('' === trim($value)) {
                throw new \Exception('The password cannot be empty');
            }

            return $value;
        });
        $question->setHidden(true);
        $plaintextPassword = $helper->ask($input, $output, $question);

        $user = new User($username, $plaintextPassword, $emailAddress);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setDisplayName($username);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User %s created', $username));

        return Command::SUCCESS;
    }
}
