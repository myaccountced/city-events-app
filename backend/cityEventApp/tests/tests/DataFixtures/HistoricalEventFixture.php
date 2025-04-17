<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class HistoricalEventFixture extends Fixture
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
                'title' => 'Christmas Market',
                'description' => 'Come and check out all of the festive wares!',
                'location' => 'Praireland Park',
                'startDate' => new \DateTime('2024-11-10 12:00:00'),
                'endDate' => new \DateTime('2024-11-12 12:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Others',
                'links' => 'https://google.com',
                'creator' => 'creator'
            ],
            [
                'title' => 'Rock n Roll Concert',
                'description' => 'See rock legends perform live',
                'location' => 'Sasktel Centre',
                'startDate' => new \DateTime('2024-11-10 12:00:00'),
                'endDate' => new \DateTime('2024-11-10 12:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'links' => 'https://google.com',
                'creator' => 'creator'
            ],
            [
                'title' => 'Outdoor Concert',
                'description' => 'Grab a picnic blanket and sit down in the park to see some live music',
                'location' => 'Lions Gate Park',
                'startDate' => new \DateTime('2024-11-10 12:00:00'),
                'endDate' => new \DateTime('2024-11-12 12:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'links' => 'https://google.com',
                'creator' => 'creator'
            ],
            [
                'title' => 'RV Show',
                'description' => 'Dozens of luxury RVs are here and open for your viewing.',
                'location' => 'Praireland',
                'startDate' => new \DateTime('2024-11-10 12:00:00'),
                'endDate' => new \DateTime('2024-11-12 12:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Sports',
                'links' => 'https://google.com',
                'creator' => 'creator'
            ],
            [
                'title' => 'Dog Show',
                'description' => 'This pet friendly event is perfect for any dog-lovers.',
                'location' => 'Some Place',
                'startDate' => new \DateTime('2024-11-10 12:00:00'),
                'endDate' => new \DateTime('2024-11-12 12:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Others',
                'links' => 'https://google.com',
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
            $eventCat->setCategoryName($eventData['category']);
            $event->addCategory($eventCat);

            $event->setEventLink($eventData['links']);
            $event->setEventCreator($eventData['creator']);
            $event->setModeratorApproval(true);
            $event->setUserId($creator);

            $manager->persist($event);
            $manager->persist($eventCat);
        }

        $manager->flush();

        $categories = [
            'Arts and Culture', 'Education', 'Health and Wellness',
            'Food and Drink', 'Music', 'Nature and Outdoors',
            'Sports', 'Technology', 'Others'
        ];
        for ($i = 1; $i <= 30; $i++) {
            $event = new Event();
            $event->setEventTitle('Event Title ' . $i);
            $event->setEventDescription('Description for event ' . $i);
            $event->setEventLocation('Saskatoon');
            $event->setEventStartDate(new \DateTime('2024-01-01'));
            $event->setEventEndDate(new \DateTime('2024-01-01'));
            $event->setEventAudience('General');

            $eventCat2 = new Category();
            $eventCat2->setCategoryName($categories[$i % count($categories)]);
            $event->addCategory($eventCat2);

            $event->setEventLink('http://example.com/event' . $i);
            $event->setEventCreator('creator');
            $event->setModeratorApproval(true);
            $event->setUserId($creator);

            $manager->persist($event);
            $manager->persist($eventCat2);
        }

        $manager->flush();
    }
}