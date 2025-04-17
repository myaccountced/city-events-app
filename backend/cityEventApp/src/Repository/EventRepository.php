<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\throwException;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
    * Fetch active events with optional filters and sorting.
    */
    public function findActiveEventsWithFilterAndSorter(
        int $limit,
        int $offset,
        array $filters = [],
        ?string $sortField = 'eventStartDate',
        ?string $sortOrder = 'ASC',
        bool $isHistoric,
        ?string $searchString
    ) {
        date_default_timezone_set('Canada/Central');
        $todayDate = new \DateTime();

        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->leftJoin('e.categories', 'c')->distinct()
            ->where('e.reportCount < 3');

        //$baseQuery = 'SELECT e FROM App\Entity\Event e';
        if ($isHistoric) {
            //$baseQuery .= ' WHERE e.eventEndDate < :todayDate AND e.reportCount < 3';
            $qb = $qb->andWhere('e.eventEndDate < :todayDate');
        } else {
            //$baseQuery = 'SELECT e FROM App\Entity\Event e WHERE e.eventEndDate >= :todayDate AND e.moderatorApproval = true AND e.reportCount < 3';
            //$baseQuery .= ' WHERE e.eventEndDate >= :todayDate AND e.reportCount < 3';
            $qb = $qb->andWhere('e.eventEndDate >= :todayDate');
        }

        $queryParams = [];

        if ($searchString && $searchString != '') {
            $queryParams = [
                'todayDate' => $todayDate,
                'search' => '%'.$searchString.'%'
            ];
        } else {
            $queryParams = [
                'todayDate' => $todayDate
            ];
        }


        // Handle dynamic filters
        foreach ($filters as $field => $values) {

            if (!empty($values) && is_array($values)) {
                $paramKeys = [];

                if ($field == 'eventCategory')
                {
                    $catQueryParams = [];

                    foreach ($values as $index => $value) {
                        $paramKey = "{$field}_{$index}";
                        $paramKeys[] = ":{$paramKey}";
                        $catQueryParams[$paramKey] = $value;
                    }

                    $catQueryParams['count_num'] = count($paramKeys);

                    $cats = $this->getEntityManager()->createQueryBuilder()
                        ->select('e1.id')
                        ->from(Category::class, 'c1')
                        ->join('c1.event', 'e1')
                        ->where('c1.categoryName IN (' . implode(', ', $paramKeys) . ')')
                        ->groupBy('c1.event')->having('COUNT(e1.id) = :count_num')
                        ->getQuery()
                        ->setParameters($catQueryParams);

                    $resulty = $cats->getResult();
                    $paramKeys2 = [];
                    foreach ($resulty as $index => $value) {
                        $paramKey2 = "{$field}_{$index}";
                        $paramKeys2[] = ":{$paramKey2}";
                        $queryParams[$paramKey2] = $value;
                    }

                    $qb = $qb->andWhere('e.id IN (' . implode(', ', $paramKeys2) . ')');
                }
                else if ($field == 'eventStartDate' || $field == 'eventEndDate')
                {
                    $dateQuery = "";

                    foreach ($values as $index => $value) {
                        //print_r($value);
                        $tempDate = new \DateTime($value);

                        if ($index != 0) {
                            $dateQuery .= " OR ";
                        }


                        $paramKey = "{$field}_{$index}";
                        $queryParams[$paramKey] = $tempDate->format('Y-m-d H:i:s');
                        $dateQuery .= "( e." . $field . " BETWEEN :" . $paramKey;

                        $tempDate->add(new \DateInterval('PT23H59M59S'));

                        $paramKey = "{$field}_{$index}_{$index}";
                        $queryParams[$paramKey] = $tempDate->format('Y-m-d H:i:s');
                        $dateQuery .= " AND :" . $paramKey . " )";

                        break;
                    }

                    $qb = $qb->andWhere($dateQuery);
                }
                else
                {
                    foreach ($values as $index => $value) {
                        $paramKey = "{$field}_{$index}";
                        $paramKeys[] = ":{$paramKey}";
                        $queryParams[$paramKey] = $value;
                        $qb = $qb->andWhere('e.' . $field . ' = :' . $paramKey);
                    }
                    //$qb = $qb->andWhere('e.' . $field . ' IN (' . implode(', ', $paramKeys) . ')');
                }

            }
        }

        if ($searchString && $searchString != '') {
//            $baseQuery .= ' AND ( e.eventTitle LIKE :search
//                    OR e.eventDescription LIKE :search
//                    OR e.eventLocation LIKE :search
//                    OR c.categoryName LIKE :search )';
            $bq = $qb->andWhere('e.eventTitle LIKE :search 
                    OR e.eventDescription LIKE :search 
                    OR e.eventLocation LIKE :search 
                    OR c.categoryName LIKE :search');
        }

        // Handle sorting dynamically
        $validSortFields = ['eventStartDate', 'eventTitle', 'eventLocation'];
        if (in_array($sortField, $validSortFields)) {
            //$baseQuery .= " ORDER BY e.{$sortField} " . ($sortOrder === 'DESC' ? 'DESC' : 'ASC') . ($sortField === 'eventLocation' ? ', e.eventStartDate ASC, e.eventTitle ASC' : '');
            $qb = $qb->orderBy('e.' . $sortField, $sortOrder === 'DESC' ? 'DESC' : 'ASC');

            if ($sortField == 'eventLocation') {
                $qb = $qb->addOrderBy('e.eventStartDate', 'ASC')->addOrderBy('e.eventTitle', 'ASC');
            }
        } else {
            $qb = $qb->orderBy('e.eventStartDate', 'ASC')->addOrderBy('e.eventTitle', 'ASC')->addOrderBy('e.eventLocation', 'ASC');
        }

        return $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->setParameters($queryParams)->getResult();
    }

    public function findCurrentOrPastEvents(int $limit, int $offset, ?string $searchString, ?bool $isHistoric)
    {
        date_default_timezone_set('Canada/Central');
        $entityManager = $this->getEntityManager();

        $todayDate = new \DateTime();

        $query = $entityManager->createQuery(
            'SELECT e FROM App\Entity\Event e
            WHERE e.eventEndDate >= :todayDate
            AND e.moderatorApproval = true
            AND e.reportCount < 3
            ORDER BY e.eventStartDate ASC, e.eventTitle ASC, e.eventLocation ASC'
        )->setParameter('todayDate', $todayDate)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getResult();
    }



    public function findSortedCurrentEvents(int $limit, int $offset, string $field, string $order)
    {
        date_default_timezone_set('Canada/Central');
        $entityManager = $this->getEntityManager();
        $todayDate = new \DateTime();

        // Define the allowed fields for sorting to prevent SQL injection
        $allowedFields = [
            'startDate' => 'e.eventStartDate',
            'title' => 'e.eventTitle',
            'location' => 'e.eventLocation'
        ];

        // Build ORDER BY clause based on primary sort field
        $orderByClause = match($field) {
            'startDate' => sprintf(
                '%s %s, %s ASC, %s ASC',
                $allowedFields['startDate'],
                strtoupper($order),
                $allowedFields['title'],
                $allowedFields['location']
            ),
            'title' => sprintf(
                '%s %s, %s ASC, %s ASC',
                $allowedFields['title'],
                strtoupper($order),
                $allowedFields['startDate'],
                $allowedFields['location']
            ),
            'location' => sprintf(
                '%s %s, %s ASC, %s ASC',
                $allowedFields['location'],
                strtoupper($order),
                $allowedFields['startDate'],
                $allowedFields['title']
            ),
            default => 'e.eventStartDate ASC, e.eventTitle ASC, e.eventLocation ASC'
        };
        $query = $entityManager->createQuery(
            'SELECT e FROM App\Entity\Event e 
        WHERE e.eventEndDate >= :todayDate 
        AND e.moderatorApproval = true
        AND e.reportCount < 3
        ORDER BY ' . $orderByClause
        )
            ->setParameter('todayDate', $todayDate)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getResult();
    }


    /**
     * Returns a single event from the Event database.
     * @param int $eventID id of the event
     * @return Event event that matches the given id
     * @throws Exception thrown if no event exists
     */
    public function getEventByID(int $eventID): Event
    {
        $eventReturn = $this->findOneBy(['id' => $eventID]);

        if (!$eventReturn)
        {
            throw new Exception("Event with id $eventID doesn't exist");
        }
        else
        {
            return $eventReturn;
        }
    }


    /**
     * This method will set an event's moderator approval status to true, false, or null.
     * @param Event $event Event to be updated.
     * @param bool|null $approval Approval status (true/false/null)
     * @return Event newly updated event
     */
    public function approveOrRejectEvent(Event $event, ?bool $approval): Event
    {
        throw new Exception("Not implemented");
    }

    /** Get events that has been reported three times.
     * @param int $limit The maximum amount of events we can fetch
     * @param int $offset Starting point for the query results. Example if we have 20 for offset, we will return 21st result onwards.
     * @return mixed
     */
    public function findReportedEvents(int $limit, int $offset)
    {
        date_default_timezone_set('Canada/Central');
        $entityManager = $this->getEntityManager();

        $todayDate = new \DateTime();

        $query = $entityManager->createQuery(
            'SELECT e FROM App\Entity\Event e
            WHERE e.eventEndDate >= :todayDate
            AND e.moderatorApproval = true
            AND e.reportCount = 3
            ORDER BY e.eventStartDate ASC, e.eventTitle ASC'
        )
            ->setParameter('todayDate', $todayDate)
            ->setMaxResults($limit)
            ->setFirstResult($offset);;

        return $query->getResult();
    }

    /**
     * Get the series of events using the given eventID of the parent event. Only returns the future/upcoming events.
     * @param int $event
     * @return Event[]
     */
    public function findEventSeriesUpcomingOnly(int $eventID)
    {
        date_default_timezone_set('Canada/Central');
        $todayDate = new \DateTime();

        return $this->createQueryBuilder('e')
            ->where('e.eventEndDate >= :todayDate')
            ->andWhere('e.parentEventID = :eventID OR e.id = :eventID')
            ->setParameter('todayDate', $todayDate)
            ->setParameter('eventID', $eventID)
            ->orderBy('e.eventStartDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the series of events using the given eventID of the parent event. Returns both upcoming and past events
     * @param int $event
     * @return Event[]
     */
    public function findEventSeries(int $eventID)
    {
        date_default_timezone_set('Canada/Central');

        return $this->createQueryBuilder('e')
            ->andWhere('e.parentEventID = :eventID OR e.id = :eventID')
            ->setParameter('eventID', $eventID)
            ->orderBy('e.eventStartDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
