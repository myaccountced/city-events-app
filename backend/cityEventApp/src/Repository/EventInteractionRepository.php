<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventInteraction;
use App\Entity\EventInteractionStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class EventInteractionRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, EventInteraction::class);
        $this->entityManager = $entityManager;
    }
}