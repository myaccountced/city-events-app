<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class lotsOfEvents extends Fixture
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

        $categories = [
            'Arts and Culture', 'Education', 'Health and Wellness',
            'Food and Drink', 'Music', 'Nature and Outdoors',
            'Sports', 'Technology', 'Others'
        ];

        // DO NOT USE Estevan or Yorkton!!! Those are reserved for the first and last location sort
        $cities = [
            'Saskatoon', 'Regina', 'Prince Albert', 'Moose Jaw', 'North Battleford', 'Warman'
        ];

        // 'Adult Only' will be first and 'Youth' will be last
        $audiences = [
            'Family Friendly', 'Adult Only', 'General', 'Youth', 'Teens and Up'
        ];

        for ($i = 1; $i <= 1000; $i++) {
            $event = new Event();
            $event->setEventTitle('Event Title ' . $i);
            $event->setEventDescription('Description for event ' . $i);
            $event->setEventLocation($cities[$i % count($cities)]);
            $event->setEventStartDate(new \DateTime('2026-01-01'));
            $event->setEventEndDate(new \DateTime('2026-01-01'));
            $event->setEventAudience($audiences[$i % count($audiences)]);

            $eventCat = new Category();
            $eventCat->setCategoryName($categories[$i % count($categories)]);
            $event->addCategory($eventCat);
            //$event->setEventCategory($categories[$i % count($categories)]);

            $event->setEventLink('http://example.com/event' . $i);
            $event->setEventCreator('creator');
            $event->setModeratorApproval(true);

            $event->setUserId($creator);

            $manager->persist($event);
            $manager->persist($eventCat);
        }

        $manager->flush();
    }
}
