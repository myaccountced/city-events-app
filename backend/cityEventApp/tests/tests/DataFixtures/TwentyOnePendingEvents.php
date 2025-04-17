<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Event;

class TwentyOnePendingEvents extends Fixture
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

        // Array of event data
        $eventsData = [
            [
                'title' => 'Pending Event 21',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-21 13:00:00'),
                'endDate' => new \DateTime('2026-01-21 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],

            [
                'title' => 'Pending Event 02',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-02 13:00:00'),
                'endDate' => new \DateTime('2026-01-02 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 03',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-03 13:00:00'),
                'endDate' => new \DateTime('2026-01-03 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 04',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-04 13:00:00'),
                'endDate' => new \DateTime('2026-01-04 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 05',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-05 13:00:00'),
                'endDate' => new \DateTime('2026-01-05 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],

            [
                'title' => 'Pending Event 07',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-07 13:00:00'),
                'endDate' => new \DateTime('2026-01-07 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 16',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-16 13:00:00'),
                'endDate' => new \DateTime('2026-01-16 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 06',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-06 13:00:00'),
                'endDate' => new \DateTime('2026-01-06 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 08',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-08 13:00:00'),
                'endDate' => new \DateTime('2026-01-08 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 09',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-09 13:00:00'),
                'endDate' => new \DateTime('2026-01-09 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 10',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-10 13:00:00'),
                'endDate' => new \DateTime('2026-01-10 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 11',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-10 13:00:00'),
                'endDate' => new \DateTime('2026-01-10 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 12',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-12 13:00:00'),
                'endDate' => new \DateTime('2026-01-12 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 13',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-13 13:00:00'),
                'endDate' => new \DateTime('2026-01-13 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 14',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-14 13:00:00'),
                'endDate' => new \DateTime('2026-01-14 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 15',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-15 13:00:00'),
                'endDate' => new \DateTime('2026-01-15 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],

            [
                'title' => 'Pending Event 19',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-19 13:00:00'),
                'endDate' => new \DateTime('2026-01-19 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 17',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-17 13:00:00'),
                'endDate' => new \DateTime('2026-01-17 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 18',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-18 13:00:00'),
                'endDate' => new \DateTime('2026-01-18 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],

            [
                'title' => 'Pending Event 20',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-20 13:00:00'),
                'endDate' => new \DateTime('2026-01-20 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'Pending Event 01',
                'description' => 'Test Event Description',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2026-01-01 13:00:00'),
                'endDate' => new \DateTime('2026-01-01 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],
            [
                'title' => 'TApproved Event',
                'description' => 'Test Event Description',
                'location' => 'Warman',
                'startDate' => new \DateTime('2026-02-01 13:00:00'),
                'endDate' => new \DateTime('2026-02-01 13:00:00'),
                'audience' => 'Family Friendly',
                'category' => 'Arts and Culture',
                'creator' => 'creator'
            ],

        ];

        foreach($eventsData as $eventData) {
            $event = new Event();
            if($eventData['title'] == 'TApproved Event') {
                $event->setEventTitle($eventData['title']);
                $event->setEventDescription($eventData['description']);
                $event->setEventLocation($eventData['location']);
                $event->setEventStartDate($eventData['startDate']);
                $event->setEventEndDate($eventData['endDate']);
                $event->setEventAudience($eventData['audience']);

                $eventCat = new Category();
                $eventCat->setCategoryName($eventData['category']);
                $event->addCategory($eventCat);

                $event->setEventCreator($eventData['creator']);
                $event->setModeratorApproval(true);
                $event->setUserId($creator);
                $manager->persist($event);
                $manager->persist($eventCat);
            }
            else
            {
                $event->setEventTitle($eventData['title']);
                $event->setEventDescription($eventData['description']);
                $event->setEventLocation($eventData['location']);
                $event->setEventStartDate($eventData['startDate']);
                $event->setEventEndDate($eventData['endDate']);
                $event->setEventAudience($eventData['audience']);

                $eventCat = new Category();
                $eventCat->setCategoryName($eventData['category']);
                $event->addCategory($eventCat);

                $event->setEventCreator($eventData['creator']);
                $event->setModeratorApproval(false);
                $event->setUserId($creator);
                $manager->persist($event);
                $manager->persist($eventCat);
            }
        }

        // Also need to add a moderator
        // Password SHOULD be 'ABC123def'
        $userData = [
            'username'=> 'Moderator3',
            'email' => 'mod1@test.com',
            'password' => '$2y$13$lwnPsCTueHsyIMYNmbPvR.B3OWc55pEtHuMQQM/3TzeUQCcDasl1G',
            'roles' => ['ROLE_MODERATOR']
        ];


        $product = new User();
        $product->setUsername($userData['username']);
        $product->setEmail($userData['email']);
        $product->setPassword($userData['password']);
        $product->setModerator(true);
        $product->setRoles($userData['roles']);
        $manager->persist($product);


        $manager->flush();

        $manager->flush();

        $this->loadMedia($manager);
    }

    private function loadMedia(ObjectManager $manager): void
    {
        // Define the events and media paths
        $events = [
            'Pending Event 01' => 1,
            'Pending Event 02' => 2,
            'Pending Event 03' => 3
        ];
        foreach ($events as $eventTitle => $mediaCount) {
            $event = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => $eventTitle]);

            for ($i = 1; $i <= $mediaCount; $i++) {
                $media = new Media();
                $media->setEvent($event);
                if ($i == 2) {
                    $mediaPath = "chili1.jpg";
                }
                else {
                    $mediaPath = "chilidisaster.jpg";
                }
                $media->setPath($mediaPath);
                $manager->persist($media);
            }
            $manager->flush();
        }
    }
}