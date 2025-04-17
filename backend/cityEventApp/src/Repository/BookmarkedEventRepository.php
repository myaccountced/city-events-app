<?php

namespace App\Repository;

use App\Entity\BookmarkedEvent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookmarkedEvent>
 */
class BookmarkedEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookmarkedEvent::class);
    }

    public function findCurrentBookmarkedEvents(int $limit, int $offset, User $currentUser)
    {
        date_default_timezone_set('Canada/Central');
        $entityManager = $this->getEntityManager();

        $todayDate = new \DateTime();

        //$currentUserID = UserRepository::class->findUserIDFromUsername($currentUser);

        $query = $entityManager->createQuery(
            'SELECT b, e
                 FROM App\Entity\BookmarkedEvent b
                 INNER JOIN b.event e
                 WHERE b.user = :currentUser
                 AND e.eventEndDate >= :todayDate
                 ORDER BY e.eventStartDate ASC, e.eventTitle ASC, e.eventLocation ASC'
        )->setParameters(new ArrayCollection([
            new Parameter('currentUser', $currentUser),
            new Parameter('todayDate', $todayDate)
        ]))
            ->setMaxResults($limit)
            ->setFirstResult($offset);



        return $query->getResult();
    }

    public function findABookmarkedEvent(int $userID, int $eventID)
    {

        $entityManager = $this->getEntityManager();

        $qb = $entityManager->createQueryBuilder();

        $qb->select('b')
            ->from(BookmarkedEvent::class, 'b')
            ->andWhere('b.userID = :userID')
            ->andWhere('b.eventID = :eventID')
            ->setParameters(new ArrayCollection([
                new Parameter('userID', 'value for :userID'),
                new Parameter('eventID', 'value for :eventID')
            ]));

        return $qb->getQuery()->getResult();

        //$currentUserID = UserRepository::class->findUserIDFromUsername($user->getUsername());
        /*return $this->createQueryBuilder('b')
            ->andWhere('b.userID = :userID')
            ->andWhere('b.eventID = :eventID')
            ->setParameter('eventID', $eventID)
            ->setParameter('userID', $userID)
            ->getQuery()
            ->getOneOrNullResult()
        ;*/
    }

    /**
     * This method is to query Bookmarked table to check user who has bookmark events that will
     * happen in specific time
     * @param User $user
     * @param \DateTime $date
     * @return mixed
     */
    public function findBookmarkedEventsForUserByNotificationTime(User $curUser): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.event', 'e')
            ->where('b.user = :user')
            ->setParameter('user', $curUser);

        $today = new \DateTime();
        $tomorrow = (clone $today)->modify('+1 day');
        $nextWeek = (clone $today)->modify('+7 days');

        $conditions = [];
        $parameters = [];

        if (in_array('day0', $curUser->getNotificationTimes())) {
            $conditions[] = '(e.eventStartDate >= :startToday AND e.eventStartDate <= :endToday)';
            $parameters['startToday'] = $today->format('Y-m-d') . ' 00:00:00';
            $parameters['endToday'] = $today->format('Y-m-d') . ' 23:59:59';
        }
        if (in_array('day1', $curUser->getNotificationTimes())) {
            $conditions[] = '(e.eventStartDate >= :startTomorrow AND e.eventStartDate <= :endTomorrow)';
            $parameters['startTomorrow'] = $tomorrow->format('Y-m-d') . ' 00:00:00';
            $parameters['endTomorrow'] = $tomorrow->format('Y-m-d') . ' 23:59:59';
        }
        if (in_array('day7', $curUser->getNotificationTimes())) {
            $conditions[] = '(e.eventStartDate >= :startNextWeek AND e.eventStartDate <= :endNextWeek)';
            $parameters['startNextWeek'] = $nextWeek->format('Y-m-d') . ' 00:00:00';
            $parameters['endNextWeek'] = $nextWeek->format('Y-m-d') . ' 23:59:59';
        }

        if (!empty($conditions)) {
            $qb->andWhere(implode(' OR ', $conditions));
            // Set only the required parameters
            foreach ($parameters as $key => $value) {
                $qb->setParameter($key, $value);
            }
        }

        return $qb->getQuery()->getResult();
    }

}
