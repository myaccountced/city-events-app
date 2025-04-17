<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByUsername(string $username)
    {
        /*throw $this->createNotFoundException(
            $username . ' not found'
        );*/

        $entityManager = $this->getEntityManager();

        $qb = $entityManager->createQueryBuilder();

        $qb->select('u')
            ->from(User::class, 'u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username);

        return $qb->getQuery()->getResult();
    }

    /*
     * finds the identifier in the username or email column
     */
    public function findOneByIdentifier(string $identifier) : ?User
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT u FROM App\Entity\User u 
             WHERE u.username = :identifier OR u.email = :identifier'
            )
            ->setParameter('identifier', $identifier)
            ->getOneOrNullResult();
    }

    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }


    /**
     * Finds users that have usernames or emails containing text like usernameOrEmail string,
     *  taking into account which users to return based on the sort category, the amount to take,
     *  and the offset from 0 from where to start.
     *
     * @param string $usernameOrEmail string that is like a username or email
     * @param string $sortCategory string of a user property to sort by. Defaults to username
     * @param string $sortOrder string 'ASC' or 'DESC' for sorting in ascending or descending order. Defaults to 'ASC'
     * @param int $limit maximum number of users to return. Defaults to 20
     * @param int $offset number of users to skip before starting the return group. Defaults to 0
     * @return array array of Users
     */
    public function findUserLike(string $usernameOrEmail, string $sortCategory = 'username',
                                 string $sortOrder = 'ASC', int $limit = 20, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('u')
            ->andWhere('u.username LIKE :usernameOrEmail')
            ->orWhere('u.email LIKE :usernameOrEmail')
            ->setParameter('usernameOrEmail', "%" . $usernameOrEmail . "%")
            ->orderBy('u.' . $sortCategory, $sortOrder)
            ->setMaxResults($limit)
            ->setFirstResult($offset)->getQuery();

        return $query->getResult();
    }

    /**
     * This method is list all users who want to receive notification via email,
     * and specified the time they want to get notified
     * @param string $notificationTiming
     * @return mixed
     */

    public function findUsersWithEmailNotification(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
        SELECT id FROM user u
        WHERE u.wantsNotification = true
        AND JSON_CONTAINS(u.notification_methods, :email)
        AND JSON_LENGTH(u.notification_times) > 0
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('email', json_encode('email'));
        //$stmt->execute();

        $userIds = array_column($stmt->execute()->fetchAllAssociative(), 'id');

        if (empty($userIds)) {
            return [];
        }

        return $this->getEntityManager()->getRepository(User::class)->findBy(['id' => $userIds]);
    }


}
