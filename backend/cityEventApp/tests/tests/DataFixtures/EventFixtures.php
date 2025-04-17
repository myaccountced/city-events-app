<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use App\Entity\BookmarkedEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Start of section 1
        $user1 = new User();
        $user1->setUsername("user1");
        $user1->setEmail("user1@example.com");
        $user1->setPassword("$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2"); //@Password1
        $user1->setRoles(["ROLE_REGISTERED"]);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setUsername("user2");
        $user2->setEmail("user2@example.com");
        $user2->setPassword("$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2"); //@Password1
        $user2->setRoles(["ROLE_REGISTERED"]);
        $manager->persist($user2);
        $manager->flush();

        // Event data with six events
        $eventData = [
            [
                'title' => "Event A",
                'description' => 'Description A',
                'location' => 'Moose Jaw',
                'startDate' => new \DateTime('2026-02-01'),
                'endDate' => new \DateTime('2026-02-01'),
                'audience' => 'Adult Only',
                'category' => 'Arts and Culture',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
            [
                'title' => "Event B",
                'description' => 'Description B',
                'location' => 'North Battleford',
                'startDate' => new \DateTime('2026-02-02'),
                'endDate' => new \DateTime('2026-02-02'),
                'audience' => 'Family Friendly',
                'category' => 'Sports',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
            [
                'title' => "Event C",
                'description' => 'Description C',
                'location' => 'Moose Jaw',
                'startDate' => new \DateTime('2026-03-01'),
                'endDate' => new \DateTime('2026-03-01'),
                'audience' => 'General',
                'category' => 'Music',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('15:00:00'),
                'endTime' => new \DateTime('18:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
            [
                'title' => "Event D",
                'description' => 'Description D',
                'location' => 'North Battleford',
                'startDate' => new \DateTime('2026-03-01'),
                'endDate' => new \DateTime('2026-03-01'),
                'audience' => 'Teens and Up',
                'category' => 'Others',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('15:00:00'),
                'endTime' => new \DateTime('18:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
            [
                'title' => "Event E",
                'description' => 'Description E',
                'location' => 'Moose Jaw',
                'startDate' => new \DateTime('2026-03-05'),
                'endDate' => new \DateTime('2026-03-05'),
                'audience' => 'Youth',
                'category' => 'Education',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('12:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
            [
                'title' => "Event F",
                'description' => 'Description E',
                'location' => 'North Battleford',
                'startDate' => new \DateTime('2026-03-05'),
                'endDate' => new \DateTime('2026-03-05'),
                'audience' => 'Youth',
                'category' => 'Education',
                'images' => ['p1.jpg', 'chilidisaster.jpg'],
                'startTime' => new \DateTime('12:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => 'https://google.com',
                'creator' => 'user1'
            ],
        ];

        // Loop through the data and create each event
        foreach ($eventData as $data) {
            // Create new Event
            $event = new Event();
            $event->setEventTitle($data['title']);
            $event->setEventDescription($data['description']);
            $event->setEventLocation($data['location']);
            $event->setEventStartDate($data['startDate']);
            $event->setEventEndDate($data['endDate']);
            $event->setEventAudience($data['audience']);

            // Create and associate category
            $eventCat = new Category();
            $eventCat->setCategoryName($data['category']);
            $event->addCategory($eventCat);
            $event->setUserId($user1);

            // Set other event properties
            $event->setEventLink($data['links']);
            $event->setEventCreator($data['creator']);
            $event->setModeratorApproval(true);

            // Add Media (Images)
            foreach ($data['images'] as $imagePath) {
                $media = new Media();
                $media->setPath($imagePath)
                    ->setEvent($event); // Link media to the event

                $manager->persist($media); // Persist media
            }

            // Create BookmarkedEvent entities
            if ($user1 && $event) {
                $bookmark1 = new BookmarkedEvent();
                $bookmark1->setUser($user1);
                $bookmark1->setEvent($event);
                $manager->persist($bookmark1);
            }
            if ($user2 && $event) {
                $bookmark2 = new BookmarkedEvent();
                $bookmark2->setUser($user2);
                $bookmark2->setEvent($event);
                $manager->persist($bookmark2);
            }



            // Persist event and category
            $manager->persist($event);
            $manager->persist($eventCat);
        }

        $manager->flush();

        $eventRepo = $manager->getRepository(Event::class);
        $eventA = $eventRepo->findOneBy(['eventTitle' => 'Event A']);
        $eventC = $eventRepo->findOneBy(['eventTitle' => 'Event C']);


        // Create BookmarkedEvent entities
        if ($eventA) {
            $bookmark1 = new BookmarkedEvent();
            $bookmark1->setUser($user1);
            $bookmark1->setEvent($eventA);
            $manager->persist($bookmark1);
        }


        if ($eventC) {
            $bookmark2 = new BookmarkedEvent();
            $bookmark2->setUser($user2);
            $bookmark2->setEvent($eventC);
            $manager->persist($bookmark2);
        }

        $manager->flush();

        $username1 = $manager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        // the 'username1' does not exist?
        if (!$username1) {
            // Password SHOULD be @Password1
            $username1Data = [
                'username' => 'username1',
                'email' => 'username1@example.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ];

            // Making the creator
            $username1 = new User();
            $username1->setUsername($username1Data['username']);
            $username1->setEmail($username1Data['email']);
            $username1->setPassword($username1Data['password']);
            $username1->setRoles($username1Data['roles']);

            $manager->persist($username1);
        }

        //create a new event
        $event1 = new Event();
        $event1->setEventDescription("Karaoke contest with prizes!");
        $event1->setEventCreator(1);
        $event1->setEventTitle("Karaoke Night");
        $event1->setEventAudience('Adult Only');

        $eventCat1 = new Category();
        $eventCat1->setCategoryName("Music");
        $event1->addCategory($eventCat1);

        $event1->setEventLocation("Lloydminster");
        $event1->setEventStartDate(new \DateTime("2024-01-01"));
        $event1->setEventLink("https://google.com");
        $event1->setEventCreator("username1");
        $event1->setUserId($username1);

        $manager->persist($event1);
        $manager->persist($eventCat1);

        $event2 = new Event();
        $event2->setEventDescription("Steak night for couples.");
        $event2->setEventCreator(2);
        $event2->setEventTitle("Steak Night");
        $event2->setEventAudience('Teens and Up');

        $eventCat2 = new Category();
        $eventCat2->setCategoryName("Food and Drink");
        $event2->addCategory($eventCat2);

        $event2->setEventLocation("Martinsville");
        $event2->setEventStartDate(new \DateTime("2024-01-01"));
        $event2->setEventLink("https://symfony.com");
        $event2->setEventCreator("username1");
        $event2->setUserId($username1);

        $manager->persist($event2);
        $manager->persist($eventCat2);

        $event3 = new Event();
        $event3->setEventDescription("Soccer tournament with round robin");
        $event3->setEventCreator(5);
        $event3->setEventTitle("Soccer Tournament");
        $event3->setEventAudience('General');

        $eventCat3 = new Category();
        $eventCat3->setCategoryName("Sports");
        $event3->addCategory($eventCat3);

        $event3->setEventLocation("Regina");
        $event3->setEventStartDate(new \DateTime("2024-01-01"));
        $event3->setEventLink(null); //this one has no event link
        $event3->setEventCreator("username1");
        $event3->setUserId($username1);

        $manager->persist($event3);
        $manager->persist($eventCat3);

        $manager->flush();

    }
}
