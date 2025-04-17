<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

//    /**
//     * @return Subscription[] Returns an array of Subscription objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Subscription
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Find the most recent active subscription for a given user.
     *
     * @param int $userId
     * @return Subscription|null
     */
    public function findRecentActiveSubscription(int $userId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId') // Match the userId column
            ->andWhere('s.expireDate > :currentTime') // Ensure the subscription is still valid
            ->setParameter('userId', $userId)
            ->setParameter('currentTime', new \DateTimeImmutable()) // Current time for comparison
            ->orderBy('s.expireDate', 'DESC') // Order by the most recent expiration date
            ->setMaxResults(1) // Limit to one result
            ->getQuery()
            ->getOneOrNullResult(); // Return the result or null if none found
    }

}
