<?php

namespace App\Tests\tests\DataFixtures;


use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtureTwoEvent extends Fixture
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
                'location' => 'Estevan',
                'startDate' => new \DateTime('2026-01-02 12:00:00'),
                'endDate' => new \DateTime('2026-01-02 12:00:00'),
                'audience' => 'Family Friendly',
                'links' => 'links',
                'creator' => 'creator'
            ],
            [
            'title' => 'Test 2',
            'description' => 'Description 2',
            'location' => 'Yorkton',
                'startDate' => new \DateTime('2026-01-03 12:00:00'),
                'endDate' => new \DateTime('2026-01-03 12:00:00'),
            'audience' => 'Adult Only',
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

            foreach (Category::getAllCategories() as $category) {
                $eventCat = new Category();
                $eventCat->setCategoryName($category);
                $event->addCategory($eventCat);
                $manager->persist($eventCat);
            }

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