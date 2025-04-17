<?php

namespace App\Tests\tests\DataFixtures;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class EventFixtureTenEvents extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $creator = $manager->getRepository(User::class)->findOneBy(['username' => 'creator']);

        // the 'creator' does not exist?
        if (!$creator) {
            // Password SHOULD be @Password1
            $creatorData = [
                'username' => 'creator',
                'email' => 'creator@example.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ];

            // Making the creator
            $creator = new User();
            $creator->setUsername($creatorData['username']);
            $creator->setEmail($creatorData['email']);
            $creator->setPassword($creatorData['password']);
            $creator->setRoles($creatorData['roles']);

            $manager->persist($creator);
        }

        date_default_timezone_set('Canada/Central');
        // Array of event data
        $eventsData = [
            [
                'title' => 'Test 1',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-04 12:00:00'),
                'endDate' => new \DateTime('2026-01-04 12:00:00'),
                'audience' => 'Adults Only',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 2',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-04 12:00:00'),
                'endDate' => new \DateTime('2026-01-04 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 3',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-05 12:00:00'),
                'endDate' => new \DateTime('2026-01-05 12:00:00'),
                'audience' => 'Adults Only',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 4',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-06 12:00:00'),
                'endDate' => new \DateTime('2026-01-13 12:00:00'),
                'audience' => 'Adults Only',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 5',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-07 12:00:00'),
                'endDate' => new \DateTime('2026-02-01 12:00:00'),
                'audience' => 'Adults Only',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 6',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-09 12:00:00'),
                'endDate' => new \DateTime('2026-01-09 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 7',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-10 12:00:00'),
                'endDate' => new \DateTime('2026-01-10 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 8',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-10 12:00:00'),
                'endDate' => new \DateTime('2026-01-10 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 9',
                'description' => 'Description 1',
                'location' => 'Regina',
                'startDate' => new \DateTime('2026-01-11 12:00:00'),
                'endDate' => new \DateTime('2026-01-11 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
                'title' => 'Test 10',
                'description' => 'Description 1',
                'location' => 'Warman',
                'startDate' => new \DateTime('2026-01-12 12:00:00'),
                'endDate' => new \DateTime('2026-01-12 12:00:00'),
                'audience' => 'Youth',
                'links' => 'links',
                'creator' => 'creator'
            ]
        ];

        foreach ($eventsData as $eventData) {
            $event = new Event();
            $event->setEventTitle($eventData['title']);
            $event->setEventDescription($eventData['description']);
            $event->setEventLocation($eventData['location']);
            $event->setEventStartDate($eventData['startDate']);
            $event->setEventEndDate($eventData['endDate']);
            $event->setEventAudience($eventData['audience']);

            $eventCat = new Category();
            $eventCat->setCategoryName("Arts and Culture");
            $event->addCategory($eventCat);

            $event->setEventLink($eventData['links']);
            $event->setEventCreator($eventData['creator']);
            $event->setModeratorApproval(true);

            $event->setUserId($creator);

            $manager->persist($event);
            $manager->persist($eventCat);
        }

        $manager->flush();
    }
}