<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\ResetPasswordRequest;

#[AsCommand(
    name: 'app:expire-reset-token',
    description: 'Add a short description for your command',
)]
class ExpireResetTokenCommand extends Command
{
    private EntityManagerInterface $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve the most recent password reset request (by requestedAt)
        $tokenEntity = $this->entityManager->getRepository(ResetPasswordRequest::class)
            ->findOneBy([], ['requestedAt' => 'DESC']);

        if (!$tokenEntity) {
            $output->writeln('<error>No password reset tokens were found</error>');
            return Command::FAILURE;
        }

        // Use QueryBuilder to update the expiresAt field
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(ResetPasswordRequest::class, 'r')
            ->set('r.expiresAt', ':newDate')
            ->setParameter('newDate', new \DateTime('-1 hour'))
            ->where('r.id = :id')
            ->setParameter('id', $tokenEntity->getId());

        // Execute the query
        $qb->getQuery()->execute();

        $output->writeln('<info>Password reset token expired successfully.</info>');
        return Command::SUCCESS;
    }
}