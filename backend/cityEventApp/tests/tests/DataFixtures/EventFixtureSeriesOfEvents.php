<?php

namespace App\Tests\tests\DataFixtures;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\RecurringType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtureSeriesOfEvents extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        date_default_timezone_set('Canada/Central');

        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'username1']);
        if (!$user) {
            throw new \RuntimeException('Required user "username1" not found');
        }

        $this->loadParentEvents($manager, $user);
        $this->loadChildEvents($manager, $user);
    }

    public function loadParentEvents(ObjectManager $manager, User $user): void
    {
        // Parent of a Series 1: Delete Series Test Event With a Past Event
        // Parent of a Series 2: Delete Series Test Event Without a Past Event
        $eventData = [
            [
                'eventTitle' => "Delete Series Test Event With a Past Event",
                'eventStartDate' => new \DateTime('2025-01-01'),
                'eventEndDate' => new \DateTime('2025-01-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
            ],
            [
                'eventTitle' => "Delete Series Test Event Without a Past Event",
                'eventStartDate' => new \DateTime('2027-01-01'),
                'eventEndDate' => new \DateTime('2027-01-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
            ],
        ];

        // Loop through the data and create each parent events
        foreach ($eventData as $data) {
            $event = new Event();
            $event->setEventTitle($data['eventTitle']);
            $event->setEventDescription('This is testing the deletion of the event instance or the series.');
            $event->setEventLocation('Cemetery');

            // Combine the date and time for start and end dates
            $eventStartDate = $data['eventStartDate']->setTime($data['startTime']->format('H'), $data['startTime']->format('i'), $data['startTime']->format('s'));
            $eventEndDate = $data['eventEndDate']->setTime($data['endTime']->format('H'), $data['endTime']->format('i'), $data['endTime']->format('s'));

            // Set the start and end dates with time
            $event->setEventStartDate($eventStartDate);
            $event->setEventEndDate($eventEndDate);

            $event->setEventCreator('username1');
            $event->setEventAudience('Youth');

            $category = new Category();
            $category->setCategoryName('Arts and Culture');
            $event->addCategory($category);

            $event->setModeratorApproval(true);
            $event->setUserId($user);
            $event->setEventLink("https://google.com");
            $event->setEventRecurringType(RecurringType::MONTHLY);

            $manager->persist($event);
        }

        try {
            $manager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());

        }
    }

    public function loadChildEvents(ObjectManager $manager, User $user): void
    {
        $series1 = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => 'Delete Series Test Event With a Past Event']);
        if (!$series1) {
            throw new \RuntimeException('Required event titled "Delete Series Test Event With a Past Event" not found');
        }

        $series2 = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => 'Delete Series Test Event Without a Past Event']);
        if (!$series2) {
            throw new \RuntimeException('Required event titled "Delete Series Test Event Without a Past Event" not found');
        }

        $eventData = [
            [
                'eventTitle' => $series1->getEventTitle(),
                'eventStartDate' => new \DateTime('2027-02-01'),
                'eventEndDate' => new \DateTime('2027-02-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
                'parentEventID' => $series1->getId(),
            ],
            [
                'eventTitle' => $series1->getEventTitle(),
                'eventStartDate' => new \DateTime('2027-03-01'),
                'eventEndDate' => new \DateTime('2027-03-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
                'parentEventID' => $series1->getId(),
            ],
            [
                'eventTitle' => $series1->getEventTitle(),
                'eventStartDate' => new \DateTime('2027-04-01'),
                'eventEndDate' => new \DateTime('2027-04-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
                'parentEventID' => $series1->getId(),
            ],
            [
                'eventTitle' => $series2->getEventTitle(),
                'eventStartDate' => new \DateTime('2027-02-01'),
                'eventEndDate' => new \DateTime('2027-02-02'),
                'startTime' => new \DateTime('09:00:00'),
                'endTime' => new \DateTime('10:00:00'),
                'parentEventID' => $series2->getId(),
            ],
        ];

        // Loop through the data and create each parent events
        foreach ($eventData as $data) {
            $event = new Event();
            $event->setEventTitle($data['eventTitle']);
            $event->setEventDescription('This is testing the deletion of the event instance or the series.');
            $event->setEventLocation('Cemetery');

            // Combine the date and time for start and end dates
            $eventStartDate = $data['eventStartDate']->setTime($data['startTime']->format('H'), $data['startTime']->format('i'), $data['startTime']->format('s'));
            $eventEndDate = $data['eventEndDate']->setTime($data['endTime']->format('H'), $data['endTime']->format('i'), $data['endTime']->format('s'));

            // Set the start and end dates with time
            $event->setEventStartDate($eventStartDate);
            $event->setEventEndDate($eventEndDate);

            $event->setEventCreator('username1');
            $event->setEventAudience('Youth');

            $category = new Category();
            $category->setCategoryName('Arts and Culture');
            $event->addCategory($category);

            $event->setModeratorApproval(true);
            $event->setUserId($user);
            $event->setParentEventID($data['parentEventID']);
            $event->setEventLink("https://google.com");
            $event->setEventRecurringType(RecurringType::MONTHLY);

            $manager->persist($event);
        }

        try {
            $manager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());

        }
    }
}
