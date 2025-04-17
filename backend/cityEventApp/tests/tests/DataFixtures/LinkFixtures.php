<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;
class LinkFixtures extends Fixture
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

        $eventData = [
        [
            'title' => "Karaoke contest with prizes!",
            'description' => 'Description D',
            'location' => 'Saskatoon',
            'startDate' => new \DateTime('2024-01-01'),
            'endDate' => new \DateTime('2026-03-01'),
            'audience' => 'Adult Only',
            'category' => 'Music',
            'startTime' => new \DateTime('15:00:00'),
            'endTime' => new \DateTime('18:00:00'),
            'links' => 'https://google.com',
            'creator' => 'creator'
        ],
            [
                'title' => "Steak night for couples.",
                'description' => 'Description E',
                'location' => 'Regina',
                'startDate' => new \DateTime('2024-01-01'),
                'endDate' => new \DateTime('2026-03-05'),
                'audience' => 'Adult Only',
                'category' => 'Food and Drink',
                'startTime' => new \DateTime('12:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => null,
                'creator' => 'creator'
            ],
            [
                'title' => "Soccer tournament with round robin",
                'description' => 'Description E',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2024-01-01'),
                'endDate' => new \DateTime('2026-03-05'),
                'audience' => 'General',
                'category' => 'Sports',
                'startTime' => new \DateTime('12:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => 'https://symfony.com',
                'creator' => 'creator'
            ]
        ];

        $i = 75;
        foreach ($eventData as $data) {
            $event = new Event();
            $event->setEventTitle($data['title']);
            $event->setEventDescription($data['description']);
            $event->setEventLocation($data['location']);
            $event->setEventStartDate($data['startDate']);
            $event->setEventEndDate($data['endDate']);
            $event->setEventAudience($data['audience']);

            $eventCat = new Category();
            $eventCat->setCategoryName($data['category']);
            $event->addCategory($eventCat);

            $event->setEventLink($data['links']);
            $event->setEventCreator($data['creator']);
            $event->setModeratorApproval(true);
            $event->setUserId($creator);

            $manager->persist($event);
            $manager->persist($eventCat);
            $this->addReference('event-' . ($i++), $event);
        }
        $manager->flush();
        $i=null;
    }
}