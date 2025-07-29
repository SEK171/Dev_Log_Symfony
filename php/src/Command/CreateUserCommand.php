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

// a command class to generate a user
// context is php bin/console app:create-user name password --admin

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user account',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Plain password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Set user as admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get username
        $username = $input->getArgument('username');
        if (!$username) {
            $username = $io->ask('Enter username', null, function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Username cannot be empty');
                }
                return $value;
            });
        }

        // Get password
        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askHidden('Enter password', function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Password cannot be empty');
                }
                if (strlen($value) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters');
                }
                return $value;
            });
        }

        // Create user
        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setPermission($input->getOption('admin') ? 0 : 1);
        $user->setRegistered(new \DateTime());
        
        // Add to database
        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf(
            'Created %s user: %s',
            $input->getOption('admin') ? 'ADMIN' : 'regular',
            $username
        ));

        return Command::SUCCESS;
    }
}